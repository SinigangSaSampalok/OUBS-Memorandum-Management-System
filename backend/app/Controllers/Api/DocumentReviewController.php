<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\RawSql;
use Dompdf\Dompdf;
use Dompdf\Options;

class DocumentReviewController extends BaseController
{
    use ResponseTrait;

    protected $documentModel;
    protected $userModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->documentModel = new DocumentModel();
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $reviewer = $this->requireActiveDocumentReviewer();
        if (!is_array($reviewer)) {
            return $reviewer;
        }

        $status = strtolower(trim((string) ($this->request->getGet('status') ?? 'pending')));
        $allowed = ['pending', 'allowed', 'not_allowed', 'all'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $query = $this->documentModel
            ->select('documents.*, users.full_name as uploaded_by_name')
            ->join('users', 'users.id = documents.uploaded_by', 'left')
            ->orderBy('documents.created_at', 'DESC');

        // Document review is only for BOR documents.
        $query->where('documents.recipient_type', 'bor');

        // Only show real uploads by default (has file).
        $query
            ->where(new RawSql('documents.file_path IS NOT NULL'))
            ->where(new RawSql("TRIM(documents.file_path) <> ''"));

        if ($status !== 'all') {
            $query->where('documents.review_status', $status);
        }

        $documents = $query->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $documents,
        ]);
    }

    public function update($id)
    {
        $reviewer = $this->requireActiveDocumentReviewer();
        if (!is_array($reviewer)) {
            return $reviewer;
        }

        $document = $this->documentModel->find((int) $id);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        if (($document['recipient_type'] ?? '') !== 'bor') {
            return $this->respond(['error' => 'This document does not require BOR review'], 400);
        }

        $currentReviewStatus = strtolower(trim((string) ($document['review_status'] ?? 'pending')));
        if (in_array($currentReviewStatus, ['allowed', 'not_allowed'], true)) {
            return $this->respond([
                'error' => 'This document review is already finalized and cannot be changed.'
            ], 409);
        }

        $json = $this->request->getJSON(true) ?? [];
        $reviewStatus = strtolower(trim((string) ($json['review_status'] ?? '')));
        $reviewNote = isset($json['review_note']) ? trim((string) $json['review_note']) : null;

        if (!in_array($reviewStatus, ['allowed', 'not_allowed'], true)) {
            return $this->respond(['error' => 'Invalid review status'], 400);
        }

        $filePath = trim((string) ($document['file_path'] ?? ''));
        if ($reviewStatus === 'allowed' && $filePath === '') {
            return $this->respond(['error' => 'Document has no uploaded file to review'], 400);
        }

        $payload = [
            'review_status' => $reviewStatus,
            'reviewed_by' => (int) $reviewer['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_note' => $reviewNote !== '' ? $reviewNote : null,
        ];

        $currentStatus = strtolower(trim((string) ($document['review_status'] ?? 'pending')));
        $isTransitioningToAllowed = $reviewStatus === 'allowed' && $currentStatus !== 'allowed';

        // Availability windows start when the document becomes accessible.
        if ($isTransitioningToAllowed) {
            $nowTs = time();
            $replyDays = (int) ($document['reply_available_days'] ?? 0);
            $downloadDays = (int) ($document['download_available_days'] ?? 0);

            if ($replyDays > 0) {
                $payload['reply_deadline_at'] = date('Y-m-d H:i:s', strtotime('+' . $replyDays . ' days', $nowTs));
            }
            if ($downloadDays > 0) {
                $payload['download_deadline_at'] = date('Y-m-d H:i:s', strtotime('+' . $downloadDays . ' days', $nowTs));
            }
        }

        // If blocked/pending again, pause availability windows.
        if ($reviewStatus === 'not_allowed') {
            $payload['reply_deadline_at'] = null;
            $payload['download_deadline_at'] = null;
        }

        $ok = $this->documentModel->update((int) $id, $payload);
        if (!$ok) {
            return $this->respond(['error' => 'Failed to update review status'], 500);
        }

        // Create notifications for approval/disapproval
        try {
            $notificationType = $reviewStatus === 'allowed' ? 'document_approved' : 'document_disapproved';
            $notificationTitle = $reviewStatus === 'allowed' ? 'Document Approved' : 'Document Disapproved';
            $notificationMessage = $reviewStatus === 'allowed' 
                ? 'Document "' . ($document['title'] ?? 'Document') . '" has been approved and is now available to recipients.'
                : 'Document "' . ($document['title'] ?? 'Document') . '" has been disapproved by the reviewer.';

            // Notify OUBS users about the review decision
            $oubsUsers = $this->userModel
                ->select('id')
                ->where('user_type', 'oubs')
                ->where('is_active', 1)
                ->findAll();

            foreach ($oubsUsers as $user) {
                $this->notificationModel->createNotification(
                    $user['id'],
                    $notificationType,
                    $notificationTitle,
                    $notificationMessage,
                    [
                        'document_id' => $document['id'],
                        'document_title' => $document['title'] ?? '',
                        'document_number' => $document['document_number'] ?? '',
                        'reviewer_name' => $reviewer['full_name'] ?? '',
                        'review_note' => $reviewNote,
                    ],
                    '/oubs/documents'
                );
            }

            // If approved, also notify recipients that the document is now available
            if ($reviewStatus === 'allowed') {
                $recipientType = $document['recipient_type'] ?? '';
                $recipientUsers = [];
                
                if ($recipientType === 'bor') {
                    $recipientUsers = $this->userModel
                        ->select('users.id')
                        ->join('bor_members', 'bor_members.user_id = users.id')
                        ->where('users.is_active', 1)
                        ->findAll();
                } elseif (in_array($recipientType, ['uac', 'uadmin'], true)) {
                    $recipientUsers = $this->userModel
                        ->select('id')
                        ->where('user_type', $recipientType)
                        ->where('is_active', 1)
                        ->findAll();
                }

                foreach ($recipientUsers as $user) {
                    $this->notificationModel->createNotification(
                        $user['id'],
                        'document_new',
                        'New Document Available',
                        'A new document "' . ($document['title'] ?? 'Document') . '" is now available for your review.',
                        [
                            'document_id' => $document['id'],
                            'document_title' => $document['title'] ?? '',
                            'document_number' => $document['document_number'] ?? '',
                            'reply_deadline_at' => $payload['reply_deadline_at'] ?? null,
                            'download_deadline_at' => $payload['download_deadline_at'] ?? null,
                        ],
                        '/recipient/documents/' . $document['id']
                    );
                }
            }
        } catch (\Exception $e) {
            // Log notification error but don't fail the review update
            log_message('error', 'Failed to create review notification: ' . $e->getMessage());
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Review updated successfully',
        ]);
    }

    public function letter($id)
    {
        $auth = $this->request->user ?? null;
        if (!is_array($auth) || empty($auth['user_id'])) {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }

        $isOubs = ($auth['user_type'] ?? '') === 'oubs';
        $activeReviewer = null;
        if (!$isOubs) {
            $activeReviewer = $this->requireActiveDocumentReviewer();
            if (!is_array($activeReviewer)) {
                return $activeReviewer;
            }
        }

        $document = $this->documentModel->find((int) $id);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        if (($document['recipient_type'] ?? '') !== 'bor') {
            return $this->respond(['error' => 'This document does not require BOR review'], 400);
        }

        $decision = strtolower(trim((string) ($document['review_status'] ?? 'pending')));
        $remarks = (string) ($document['review_note'] ?? '');
        $reviewedAt = trim((string) ($document['reviewed_at'] ?? ''));

        $signer = null;
        $reviewedBy = (int) ($document['reviewed_by'] ?? 0);
        if (in_array($decision, ['allowed', 'not_allowed'], true) && $reviewedBy > 0) {
            $signer = $this->userModel->find($reviewedBy);
        }

        if (!$signer) {
            // For pending letters, show the currently assigned reviewer (if any).
            $signer = $activeReviewer;
            if (!$signer) {
                $signer = $this->userModel
                    ->where('user_type', 'bor')
                    ->where('is_active', 1)
                    ->where('is_document_reviewer', 1)
                    ->first();
            }
        }

        $data = [
            'document' => $document,
            'decision' => $decision,
            'remarks' => $remarks,
            'reviewed_at' => $reviewedAt !== '' ? $reviewedAt : '-',
            'reviewer_name' => $signer['full_name'] ?? '',
            'reviewer_position' => $signer['position'] ?? '',
            // Only show signature after a final decision.
            'signature_image' => in_array($decision, ['allowed', 'not_allowed'], true) ? ($signer['signature_image'] ?? null) : null,
        ];

        $html = view('pdf/document_review_letter', $data);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // 8.5in x 13in (612pt x 936pt) to match the template's @page size.
        $dompdf->setPaper([0, 0, 612, 936], 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $fileName = 'letter-to-commissioner-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) ($document['document_number'] ?? (string) $id)) . '.pdf';

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($pdfOutput);
    }

    public function reviewer()
    {
        $currentUser = $this->request->user ?? null;
        if (!is_array($currentUser) || ($currentUser['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $reviewer = $this->userModel
            ->select('id, full_name, position, is_active')
            ->where('user_type', 'bor')
            ->where('is_active', 1)
            ->where('is_document_reviewer', 1)
            ->first();

        return $this->respond([
            'status' => 'success',
            'data' => $reviewer,
        ]);
    }

    public function setReviewer()
    {
        $currentUser = $this->request->user ?? null;
        if (!is_array($currentUser) || ($currentUser['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $json = $this->request->getJSON(true) ?? [];
        $userId = (int) ($json['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->respond(['error' => 'user_id is required'], 400);
        }

        $candidate = $this->userModel
            ->where('id', $userId)
            ->where('user_type', 'bor')
            ->where('is_active', 1)
            ->first();

        if (!$candidate) {
            return $this->respond(['error' => 'BOR member not found'], 404);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $db->table('users')
            ->where('user_type', 'bor')
            ->set('is_document_reviewer', 0)
            ->update();

        $db->table('users')
            ->where('id', $userId)
            ->set('is_document_reviewer', 1)
            ->update();

        $db->transComplete();
        if (!$db->transStatus()) {
            return $this->respond(['error' => 'Failed to set document reviewer'], 500);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Document reviewer assigned successfully',
            'data' => [
                'id' => (int) $candidate['id'],
                'full_name' => $candidate['full_name'],
                'position' => $candidate['position'] ?? null,
            ],
        ]);
    }

    public function unsetReviewer()
    {
        $currentUser = $this->request->user ?? null;
        if (!is_array($currentUser) || ($currentUser['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $db = \Config\Database::connect();
        $ok = $db->table('users')
            ->where('user_type', 'bor')
            ->set('is_document_reviewer', 0)
            ->update();

        if (!$ok) {
            return $this->respond(['error' => 'Failed to unset document reviewer'], 500);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Document reviewer unset successfully',
        ]);
    }

    private function requireActiveDocumentReviewer()
    {
        $authUser = $this->request->user ?? null;
        $userId = (int) ($authUser['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }

        $user = $this->userModel->find($userId);
        if (
            !$user ||
            ($user['user_type'] ?? '') !== 'bor' ||
            (int) ($user['is_active'] ?? 0) !== 1 ||
            (int) ($user['is_document_reviewer'] ?? 0) !== 1
        ) {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        return $user;
    }
}
