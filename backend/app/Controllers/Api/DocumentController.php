<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BORMemberModel;
use App\Models\DocumentModel;
use App\Models\NotificationModel;
use App\Models\ReplySlipModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class DocumentController extends BaseController
{
    use ResponseTrait;

    protected $documentModel;
    protected $replySlipModel;
    protected $userModel;
    protected $borModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->documentModel = new DocumentModel();
        $this->replySlipModel = new ReplySlipModel();
        $this->userModel = new UserModel();
        $this->borModel = new BORMemberModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $user = $this->request->user;
        
        if ($user['user_type'] === 'oubs') {
            // OUBS can see all documents
            $documents = $this->documentModel
                ->select('documents.*, users.full_name as uploaded_by_name')
                ->join('users', 'users.id = documents.uploaded_by')
                ->orderBy('documents.created_at', 'DESC')
                ->findAll();
        } else {
            // Recipients see only their type's documents
            $query = $this->documentModel
                ->select('documents.*, users.full_name as uploaded_by_name')
                ->join('users', 'users.id = documents.uploaded_by')
                ->where('recipient_type', $user['user_type'])
                ->orderBy('documents.created_at', 'DESC');

            // Document review is only for BOR documents.
            if (($user['user_type'] ?? '') === 'bor') {
                $query->where('review_status', 'allowed');
            }

            $documents = $query->findAll();
        }

        $documents = $this->reconcileCompletedStatuses($documents);

        return $this->respond([
            'status' => 'success',
            'data' => $documents
        ]);
    }

    public function show($id)
    {
        $document = $this->documentModel
            ->select('documents.*, users.full_name as uploaded_by_name, users.position as uploaded_by_position')
            ->join('users', 'users.id = documents.uploaded_by')
            ->find($id);

        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        // Check if user has access
        $user = $this->request->user;
        $isOubs = ($user['user_type'] ?? null) === 'oubs';
        $isReviewer = !$isOubs && $this->isDocumentReviewer((int) ($user['user_id'] ?? 0));
        if (!$isOubs && !$isReviewer) {
            if ($document['recipient_type'] !== $user['user_type']) {
                return $this->respond(['error' => 'Access denied'], 403);
            }
            if (($document['recipient_type'] ?? '') === 'bor' && ($document['review_status'] ?? 'allowed') !== 'allowed') {
                return $this->respond(['error' => 'Document is not accessible'], 403);
            }
        }

        $document = $this->ensureNonBorAvailabilityWindows($document);

        $resolvedStatus = $this->resolveDocumentStatus($document);
        if (($document['status'] ?? '') !== $resolvedStatus) {
            $this->documentModel->update($document['id'], ['status' => $resolvedStatus]);
            $document['status'] = $resolvedStatus;
        }

        $document = $this->appendAvailabilityFlags($document);

        return $this->respond([
            'status' => 'success',
            'data' => $document
        ]);
    }

    public function create()
    {
        // Only OUBS can create documents
        if ($this->request->user['user_type'] !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $json = $this->request->getJSON(true);
        
        $validationRules = [
            'document_number' => 'required|is_unique[documents.document_number]',
            'title' => 'required',
            'recipient_type' => 'required|in_list[bor,uac,uadmin]',
            'allow_replies' => 'permit_empty|in_list[0,1]'
        ];

        $validationMessages = [
            'document_number' => [
                'required' => 'Document number is required.',
                'is_unique' => 'Document number must be unique.',
            ],
        ];

        if (!$this->validate($validationRules, $validationMessages)) {
            return $this->respond(['errors' => $this->validator->getErrors()], 400);
        }

        $allowReplies = $this->parseAllowReplies($json['allow_replies'] ?? null);

        $data = [
            'document_number' => $json['document_number'],
            'title' => $json['title'],
            'description' => $json['description'] ?? '',
            'recipient_type' => $json['recipient_type'],
            'allow_replies' => $allowReplies,
            'uploaded_by' => $this->request->user['user_id'],
            'status' => 'pending',
            'review_status' => $json['recipient_type'] === 'bor' ? 'pending' : 'allowed',
        ];

        $documentId = $this->documentModel->insert($data, true);
        if ($documentId !== false) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Document created successfully',
                'id' => $documentId
            ], 201);
        }

        $errors = $this->documentModel->errors();
        if (!empty($errors)) {
            $firstError = array_values($errors)[0];
            return $this->respond([
                'error' => $firstError,
                'errors' => $errors
            ], 400);
        }

        return $this->respond([
            'error' => 'Failed to create document due to an unexpected server error'
        ], 500);
    }

    public function upload()
    {
        // Only OUBS can upload documents
        if ($this->request->user['user_type'] !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $file = $this->request->getFile('document');
        
        if (!$file || !$file->isValid()) {
            return $this->respond(['error' => 'No valid file uploaded'], 400);
        }

        $clientMime = $file->getClientMimeType();
        $clientExt = strtolower($file->getClientExtension() ?? '');
        if ($clientMime !== 'application/pdf' && $clientExt !== 'pdf') {
            return $this->respond(['error' => 'Only PDF files are allowed'], 400);
        }

        $documentId = $this->request->getPost('document_id');
        if (!$documentId) {
            return $this->respond(['error' => 'Document ID is required'], 400);
        }

        $document = $this->documentModel->find($documentId);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        $document = $this->documentModel->find($documentId);
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        $replyDays = $this->parsePositiveInt($this->request->getPost('reply_days'));
        $downloadDays = $this->parsePositiveInt($this->request->getPost('download_days'));
        if ($replyDays === null || $downloadDays === null) {
            return $this->respond([
                'error' => 'Reply days and download days are required and must be positive integers'
            ], 400);
        }

        $nowTs = time();
        $isBorDocument = ($document['recipient_type'] ?? '') === 'bor';
        // For BOR documents, deadlines start when review is Allowed.
        // For UAC/UAdmin, documents are immediately accessible and deadlines start now.
        $replyDeadline = $isBorDocument ? null : date('Y-m-d H:i:s', strtotime('+' . $replyDays . ' days', $nowTs));
        $downloadDeadline = $isBorDocument ? null : date('Y-m-d H:i:s', strtotime('+' . $downloadDays . ' days', $nowTs));

        // Generate unique filename
        $newName = $file->getRandomName();
        $filePath = 'uploads/documents/' . $newName;

        // Create directory if not exists
        $uploadPath = WRITEPATH . 'uploads/documents';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Remove old file (re-upload scenario)
        $oldRelPath = trim((string) ($document['file_path'] ?? ''));
        if ($oldRelPath !== '') {
            $oldAbsPath = WRITEPATH . $oldRelPath;
            if (is_file($oldAbsPath)) {
                @unlink($oldAbsPath);
            }
        }

        $file->move($uploadPath, $newName);

        // Update document with file info
        $this->documentModel->update($documentId, [
            'file_path' => $filePath,
            'file_name' => $file->getClientName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientMimeType(),
            'reply_available_days' => $replyDays,
            'download_available_days' => $downloadDays,
            'reply_deadline_at' => $replyDeadline,
            'download_deadline_at' => $downloadDeadline,
            // Document review is only for BOR documents.
            'review_status' => $isBorDocument ? 'pending' : 'allowed',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ]);

        // Create notification for successful upload to OUBS user
        try {
            $this->notificationModel->createNotification(
                $this->request->user['user_id'],
                'document_upload_success',
                'Document Upload Successful',
                'Document "' . ($document['title'] ?? 'Document') . '" has been uploaded successfully.',
                [
                    'document_id' => $documentId,
                    'document_title' => $document['title'] ?? '',
                    'document_number' => $document['document_number'] ?? '',
                    'file_name' => $file->getClientName(),
                    'file_size' => $file->getSize(),
                ],
                '/oubs/documents'
            );
        } catch (\Exception $e) {
            // Log notification error but don't fail the upload
            log_message('error', 'Failed to create upload success notification: ' . $e->getMessage());
        }

        // Create notifications for all users of the recipient type
        $this->createDocumentNotifications($document, $replyDeadline, $downloadDeadline);

        return $this->respond([
            'status' => 'success',
            'message' => 'Document uploaded successfully',
            'file_path' => $filePath,
            'file_name' => $file->getClientName(),
            'reply_deadline_at' => $replyDeadline,
            'download_deadline_at' => $downloadDeadline,
        ]);
    }

    public function download($id)
    {
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        // Check access
        $user = $this->request->user;
        $isOubs = ($user['user_type'] ?? null) === 'oubs';
        $isReviewer = !$isOubs && $this->isDocumentReviewer((int) ($user['user_id'] ?? 0));
        if (!$isOubs && !$isReviewer) {
            if ($document['recipient_type'] !== $user['user_type']) {
                return $this->respond(['error' => 'Access denied'], 403);
            }
            if (($document['recipient_type'] ?? '') === 'bor' && ($document['review_status'] ?? 'allowed') !== 'allowed') {
                return $this->respond(['error' => 'Document is not accessible'], 403);
            }
        }

        $isOubs = $user['user_type'] === 'oubs';
        $isView = $this->request->getGet('view') === '1';

        $allowReplies = (int) ($document['allow_replies'] ?? 1);
        if (!$isOubs && !$isReviewer && $allowReplies === 1 && $this->isPastDeadline($document['reply_deadline_at'] ?? null)) {
            return $this->respond([
                'error' => 'Document is closed and no longer viewable.'
            ], 403);
        }

        if (!$isOubs && !$isView && $this->isPastDeadline($document['download_deadline_at'] ?? null)) {
            return $this->respond([
                'error' => 'Download deadline has passed. The document is still viewable but no longer downloadable.'
            ], 403);
        }

        $filePath = WRITEPATH . $document['file_path'];
        
        if (!file_exists($filePath)) {
            return $this->respond(['error' => 'File not found'], 404);
        }

        return $this->response->download($filePath, null);
    }

    public function delete($id)
    {
        // Only OUBS can delete documents
        if ($this->request->user['user_type'] !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $document = $this->documentModel->find($id);
        
        if (!$document) {
            return $this->respond(['error' => 'Document not found'], 404);
        }

        // Delete file if exists
        $relPath = trim((string) ($document['file_path'] ?? ''));
        if ($relPath !== '') {
            $absPath = WRITEPATH . $relPath;
            if (file_exists($absPath)) {
                unlink($absPath);
            }
        }

        if ($this->documentModel->delete($id)) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Document deleted successfully'
            ]);
        }

        return $this->respond(['error' => 'Failed to delete document'], 500);
    }

    public function myDocuments()
    {
        $user = $this->request->user;
        
        $documents = $this->documentModel
            ->select('documents.*, users.full_name as uploaded_by_name')
            ->join('users', 'users.id = documents.uploaded_by')
            ->where('uploaded_by', $user['user_id'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $documents = $this->reconcileCompletedStatuses($documents);

        return $this->respond([
            'status' => 'success',
            'data' => $documents
        ]);
    }

    public function byRecipientType($type)
    {
        // Only OUBS can filter by recipient type
        if ($this->request->user['user_type'] !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $documents = $this->documentModel
            ->select('documents.*, users.full_name as uploaded_by_name')
            ->join('users', 'users.id = documents.uploaded_by')
            ->where('recipient_type', $type)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $documents = $this->reconcileCompletedStatuses($documents);

        return $this->respond([
            'status' => 'success',
            'data' => $documents
        ]);
    }

    private function reconcileCompletedStatuses(array $documents): array
    {
        foreach ($documents as &$document) {
            $document = $this->ensureNonBorAvailabilityWindows($document);

            $resolvedStatus = $this->resolveDocumentStatus($document);
            if (($document['status'] ?? '') !== $resolvedStatus) {
                $this->documentModel->update($document['id'], ['status' => $resolvedStatus]);
                $document['status'] = $resolvedStatus;
            }

            $document = $this->appendAvailabilityFlags($document);
        }

        return $documents;
    }

    private function ensureNonBorAvailabilityWindows(array $document): array
    {
        $recipientType = (string) ($document['recipient_type'] ?? '');
        if ($recipientType === 'bor') {
            return $document;
        }

        $filePath = trim((string) ($document['file_path'] ?? ''));
        if ($filePath === '') {
            return $document;
        }

        $replyDays = (int) ($document['reply_available_days'] ?? 0);
        $downloadDays = (int) ($document['download_available_days'] ?? 0);
        $replyDeadline = trim((string) ($document['reply_deadline_at'] ?? ''));
        $downloadDeadline = trim((string) ($document['download_deadline_at'] ?? ''));
        $reviewStatus = trim((string) ($document['review_status'] ?? ''));

        $needsReply = $replyDays > 0 && $replyDeadline === '';
        $needsDownload = $downloadDays > 0 && $downloadDeadline === '';
        $needsReviewStatus = $reviewStatus !== '' && $reviewStatus !== 'allowed';

        if (!$needsReply && !$needsDownload && !$needsReviewStatus) {
            return $document;
        }

        $baseTs = strtotime((string) ($document['updated_at'] ?? ''));
        if ($baseTs === false) {
            $baseTs = strtotime((string) ($document['created_at'] ?? ''));
        }
        if ($baseTs === false) {
            $baseTs = time();
        }

        $update = [];
        if ($needsReply) {
            $update['reply_deadline_at'] = date('Y-m-d H:i:s', strtotime('+' . $replyDays . ' days', $baseTs));
        }
        if ($needsDownload) {
            $update['download_deadline_at'] = date('Y-m-d H:i:s', strtotime('+' . $downloadDays . ' days', $baseTs));
        }
        if ($needsReviewStatus) {
            $update['review_status'] = 'allowed';
        }

        if (!empty($update)) {
            $this->documentModel->update((int) $document['id'], $update);
            $document = array_merge($document, $update);
        }

        return $document;
    }

    private function resolveDocumentStatus(array $document): string
    {
        $currentStatus = (string) ($document['status'] ?? 'pending');
        $recipientType = (string) ($document['recipient_type'] ?? '');

        $isPastReplyDeadline = $this->isPastDeadline($document['reply_deadline_at'] ?? null);
        if ($isPastReplyDeadline) {
            return 'closed';
        }

        if (!in_array($recipientType, ['bor', 'uac', 'uadmin'], true)) {
            return $currentStatus;
        }

        $totalRecipients = $this->getRecipientCount($recipientType);

        if ($totalRecipients <= 0) {
            return $currentStatus;
        }

        $totalReplies = $this->replySlipModel
            ->where('document_id', (int) $document['id'])
            ->countAllResults();

        if ($totalReplies >= $totalRecipients) {
            return 'completed';
        }

        if ($currentStatus === 'closed') {
            return 'pending';
        }

        return $currentStatus;
    }

    private function appendAvailabilityFlags(array $document): array
    {
        $allowReplies = (int) ($document['allow_replies'] ?? 1);
        $document['allow_replies'] = $allowReplies;
        $document['is_view_only'] = $allowReplies === 0;
        $document['is_reply_open'] = $allowReplies === 1 && !$this->isPastDeadline($document['reply_deadline_at'] ?? null);
        $document['is_downloadable'] = !$this->isPastDeadline($document['download_deadline_at'] ?? null);
        $document['closed_status'] = $this->resolveClosedStatus($document);

        return $document;
    }

    private function resolveClosedStatus(array $document): ?string
    {
        $currentStatus = (string) ($document['status'] ?? 'pending');
        if ($currentStatus !== 'closed') {
            return null;
        }

        $recipientType = (string) ($document['recipient_type'] ?? '');
        if (!in_array($recipientType, ['bor', 'uac', 'uadmin'], true)) {
            return 'pending';
        }

        $totalRecipients = $this->getRecipientCount($recipientType);
        if ($totalRecipients <= 0) {
            return 'pending';
        }

        $totalReplies = $this->replySlipModel
            ->where('document_id', (int) $document['id'])
            ->countAllResults();

        return $totalReplies >= $totalRecipients ? 'completed' : 'pending';
    }

    private function isPastDeadline(?string $deadline): bool
    {
        $deadline = trim((string) $deadline);
        if ($deadline === '') {
            return false;
        }

        $timestamp = strtotime($deadline);
        if ($timestamp === false) {
            return false;
        }

        return time() > $timestamp;
    }

    private function parsePositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function parseAllowReplies($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if ($value === null || $value === '') {
            return 1;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['true', 'yes', 'y', 'on'], true)) {
            return 1;
        }

        if (in_array($normalized, ['false', 'no', 'n', 'off'], true)) {
            return 0;
        }

        return 1;
    }

    private function getRecipientCount(string $recipientType): int
    {
        if (!in_array($recipientType, ['bor', 'uac', 'uadmin'], true)) {
            return 0;
        }

        if ($recipientType === 'bor') {
            return $this->borModel
                ->join('users', 'users.id = bor_members.user_id')
                ->where('users.is_active', 1)
                ->countAllResults();
        }

        return $this->userModel
            ->where('user_type', $recipientType)
            ->where('is_active', 1)
            ->countAllResults();
    }

    private function isDocumentReviewer(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return false;
        }

        return ($user['user_type'] ?? '') === 'bor'
            && (int) ($user['is_active'] ?? 0) === 1
            && (int) ($user['is_document_reviewer'] ?? 0) === 1;
    }

    private function createDocumentNotifications(array $document, ?string $replyDeadline, ?string $downloadDeadline): void
    {
        try {
            $notificationModel = new NotificationModel();
            $recipientType = $document['recipient_type'] ?? '';
            
            // Get all users of the recipient type
            $recipientUsers = [];
            if ($recipientType === 'bor') {
                $recipientUsers = $this->borModel
                    ->select('bor_members.user_id')
                    ->join('users', 'users.id = bor_members.user_id')
                    ->where('users.is_active', 1)
                    ->findAll();
                $recipientUsers = array_column($recipientUsers, 'user_id');
            } else {
                $recipientUsers = $this->userModel
                    ->select('id')
                    ->where('user_type', $recipientType)
                    ->where('is_active', 1)
                    ->findAll();
                $recipientUsers = array_column($recipientUsers, 'id');
            }

            if ($recipientType === 'bor') {
                // For BOR documents, send document_review notification only once to the active reviewer
                $activeReviewer = $this->userModel
                    ->where('user_type', 'bor')
                    ->where('is_active', 1)
                    ->where('is_document_reviewer', 1)
                    ->first();

                if ($activeReviewer) {
                    $notificationModel->createNotification(
                        $activeReviewer['id'],
                        'document_review',
                        'Document Review Required: ' . $document['title'],
                        'A document requires your review: ' . $document['title'],
                        [
                            'document_id' => $document['id'],
                            'document_title' => $document['title'],
                            'document_number' => $document['document_number'] ?? '',
                            'reply_deadline_at' => $replyDeadline,
                            'download_deadline_at' => $downloadDeadline,
                        ],
                        '/recipient/document-review'
                    );
                }
            } else {
                // Create notification for each non-BOR recipient
                foreach ($recipientUsers as $userId) {
                    $replyDaysRemaining = '';
                    if ($replyDeadline) {
                        $days = (int) ceil((strtotime($replyDeadline) - time()) / 86400);
                        $replyDaysRemaining = $days > 0 ? " (Reply deadline: {$days} days)" : '';
                    }

                    $notificationModel->createNotification(
                        $userId,
                        'document_new',
                        'New Document: ' . $document['title'],
                        'A new document has been uploaded: ' . $document['title'] . $replyDaysRemaining,
                        [
                            'document_id' => $document['id'],
                            'document_title' => $document['title'],
                            'document_number' => $document['document_number'] ?? '',
                            'reply_deadline_at' => $replyDeadline,
                            'download_deadline_at' => $downloadDeadline,
                        ],
                        '/recipient/documents/' . $document['id']
                    );
                }
            }
        } catch (\Exception $e) {
            // Log notification error but don't fail the document upload
            log_message('error', 'Failed to create document notifications: ' . $e->getMessage());
        }
    }
}

