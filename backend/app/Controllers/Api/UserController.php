<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\NotificationModel;
use CodeIgniter\API\ResponseTrait;

class UserController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function profile()
    {
        $authUser = $this->request->user ?? null;
        $userId = (int) ($authUser['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }

        $user = $this->userModel
            ->select('users.*, college_campuses.name as college_campus_name, college_campuses.type as college_campus_type')
            ->join('college_campuses', 'college_campuses.id = users.college_campus_id', 'left')
            ->where('users.id', $userId)
            ->first();

        if (!$user) {
            return $this->respond(['error' => 'User not found'], 404);
        }

        unset($user['password']);

        return $this->respond([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function updateSignature()
    {
        $authUser = $this->request->user ?? null;
        $userId = (int) ($authUser['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }

        // Get user's current signature before update
        $userBefore = $this->userModel->find($userId);
        $hadSignatureBefore = !empty($userBefore['signature_image']);

        $json = $this->request->getJSON(true) ?? [];
        $signatureInput = trim((string) ($json['signature_image'] ?? ''));
        if ($signatureInput === '') {
            return $this->respond(['error' => 'E-signature image is required'], 400);
        }

        $normalizedSignature = $this->prepareSignatureForStorage($signatureInput);
        if ($normalizedSignature === '') {
            return $this->respond(['error' => 'Invalid e-signature image'], 400);
        }

        // Require password confirmation when replacing an existing signature
        if ($hadSignatureBefore) {
            $password = trim((string) ($json['password'] ?? ''));
            if ($password === '' || !password_verify($password, $userBefore['password'])) {
                return $this->respond([
                    'error' => 'Password confirmation is required to replace the e-signature.',
                    'code' => 'SIGNATURE_PASSWORD_REQUIRED'
                ], 401);
            }
        }

        $ok = $this->userModel->update($userId, [
            'signature_image' => $normalizedSignature,
        ]);

        if (!$ok) {
            return $this->respond(['error' => 'Failed to update e-signature'], 500);
        }

        $updated = $this->userModel->find($userId);
        if (!$updated) {
            return $this->respond(['error' => 'User not found'], 404);
        }

        // Create welcome notification if this is the first time setting signature
        if (!$hadSignatureBefore) {
            try {
                $this->notificationModel->createNotification(
                    $userId,
                    'welcome',
                    'Welcome!',
                    'Please review and accept the Privacy Notice before proceeding.',
                    ['userId' => $userId],
                    '/privacy-notice'
                );
            } catch (\Exception $e) {
                log_message('error', 'Failed to create welcome notification: ' . $e->getMessage());
            }
        }

        unset($updated['password']);

        return $this->respond([
            'status' => 'success',
            'message' => 'E-signature updated successfully',
            'data' => $updated,
        ]);
    }

    private function prepareSignatureForStorage(string $signatureDataUri): string
    {
        $signatureDataUri = trim($signatureDataUri);
        if ($signatureDataUri === '') {
            return '';
        }

        if (stripos($signatureDataUri, 'data:image/') !== 0) {
            return '';
        }

        $parts = explode(',', $signatureDataUri, 2);
        if (count($parts) !== 2) {
            return '';
        }

        $meta = $parts[0];
        $encoded = $parts[1];
        if (stripos($meta, ';base64') === false) {
            return '';
        }

        $encoded = str_replace(' ', '+', trim($encoded));
        $encoded = preg_replace('/\s+/', '', $encoded) ?? $encoded;
        $binary = base64_decode($encoded, true);
        if ($binary === false || $binary === '') {
            return '';
        }

        $imageInfo = @getimagesizefromstring($binary);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return '';
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $maxWidth = 820;
        $maxHeight = 200;
        $maxOutputBytes = 450000;

        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            return 'data:image/png;base64,' . base64_encode($binary);
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return '';
        }

        $scale = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1);
        $targetWidth = max(1, (int) floor($width * $scale));
        $targetHeight = max(1, (int) floor($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);
            return '';
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        $ok = imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        imagedestroy($source);
        if (!$ok) {
            imagedestroy($target);
            return '';
        }

        $pngBinary = '';
        $written = false;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            ob_start();
            $written = imagepng($target, null, 9);
            $candidate = ob_get_clean();

            if ($written && $candidate !== false && $candidate !== '') {
                $pngBinary = $candidate;
                if (strlen($pngBinary) <= $maxOutputBytes) {
                    break;
                }
            }

            $currW = imagesx($target);
            $currH = imagesy($target);
            $nextW = max(1, (int) floor($currW * 0.85));
            $nextH = max(1, (int) floor($currH * 0.85));
            if ($nextW === $currW && $nextH === $currH) {
                break;
            }

            $next = imagecreatetruecolor($nextW, $nextH);
            if ($next === false) {
                break;
            }
            imagealphablending($next, false);
            imagesavealpha($next, true);
            $nextTransparent = imagecolorallocatealpha($next, 255, 255, 255, 127);
            imagefilledrectangle($next, 0, 0, $nextW, $nextH, $nextTransparent);
            imagecopyresampled($next, $target, 0, 0, 0, 0, $nextW, $nextH, $currW, $currH);
            imagedestroy($target);
            $target = $next;
        }

        imagedestroy($target);
        if (!$written || $pngBinary === '') {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($pngBinary);
    }
}
