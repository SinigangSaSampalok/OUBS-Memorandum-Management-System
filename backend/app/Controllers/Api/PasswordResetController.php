<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PasswordResetRequestModel;
use App\Models\UserModel;
use App\Models\NotificationModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\I18n\Time;

class PasswordResetController extends BaseController
{
    use ResponseTrait;

    protected $passwordResetRequestModel;
    protected $userModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->passwordResetRequestModel = new PasswordResetRequestModel();
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function requestReset()
    {
        $json = $this->request->getJSON(true);

        if (empty($json['full_name']) || empty($json['user_type'])) {
            return $this->respond(['error' => 'Full name and user type are required'], 400);
        }

        if (!$this->isValidRecipientType($json['user_type'])) {
            return $this->respond(['error' => 'Invalid user type'], 400);
        }

        $user = $this->userModel
            ->where('full_name', $json['full_name'])
            ->where('user_type', $json['user_type'])
            ->first();

        if (!$user) {
            return $this->respond(['error' => 'User not found'], 404);
        }

        $pendingRequest = $this->passwordResetRequestModel->getLatestPendingByUserId($user['id']);
        if ($pendingRequest) {
            return $this->respond(['error' => 'A reset request is already pending for this user'], 409);
        }

        $requestId = $this->passwordResetRequestModel->insert([
            'user_id' => $user['id'],
            'reason' => $json['reason'] ?? null,
            'status' => 'pending',
        ]);

        // Create notification for OUBS users
        try {
            $oubsUsers = $this->userModel
                ->select('id')
                ->where('user_type', 'oubs')
                ->where('is_active', 1)
                ->findAll();

            foreach ($oubsUsers as $oubsUser) {
                $this->notificationModel->createNotification(
                    $oubsUser['id'],
                    'reset_request',
                    'Password Reset Request',
                    $user['full_name'] . ' has requested a password reset.',
                    [
                        'request_id' => $requestId,
                        'user_id' => $user['id'],
                        'user_name' => $user['full_name'],
                        'user_type' => $user['user_type'],
                    ],
                    '/oubs/users'
                );
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to create password reset notification: ' . $e->getMessage());
            // Don't fail the request if notifications fail
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Password reset request sent to OUBS.'
        ], 201);
    }

    public function index()
    {
        if (($this->request->user['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $requests = $this->passwordResetRequestModel
            ->select('password_reset_requests.*, users.full_name, users.user_type, users.position, reviewers.full_name as reviewed_by_name')
            ->join('users', 'users.id = password_reset_requests.user_id')
            ->join('users as reviewers', 'reviewers.id = password_reset_requests.reviewed_by', 'left')
            ->orderBy('password_reset_requests.created_at', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    public function review($id)
    {
        if (($this->request->user['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $json = $this->request->getJSON(true);
        $action = $json['action'] ?? '';

        if (!in_array($action, ['approve', 'reject'], true)) {
            return $this->respond(['error' => 'Invalid action'], 400);
        }

        $request = $this->passwordResetRequestModel->find($id);
        if (!$request) {
            return $this->respond(['error' => 'Request not found'], 404);
        }

        if ($request['status'] !== 'pending') {
            return $this->respond(['error' => 'Only pending requests can be reviewed'], 409);
        }

        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $this->passwordResetRequestModel->update($id, [
            'status' => $newStatus,
            'reviewed_by' => $this->request->user['user_id'],
            'reviewer_note' => $json['note'] ?? null,
            'reviewed_at' => Time::now()->toDateTimeString(),
        ]);

        // Approved reset means recipient can set a new password.
        if ($newStatus === 'approved') {
            $this->userModel->builder()
                ->where('id', $request['user_id'])
                ->update(['password' => '']);
        }

        return $this->respond([
            'status' => 'success',
            'message' => "Request {$newStatus}."
        ]);
    }

    private function isValidRecipientType($userType)
    {
        return in_array($userType, ['bor', 'uac', 'uadmin'], true);
    }
}
