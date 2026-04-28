<?php

namespace App\Controllers\Api;

use App\Models\NotificationModel;
use App\Models\DocumentModel;
use App\Models\UserModel;
use App\Models\BORMemberModel;
use CodeIgniter\RESTful\ResourceController;

class NotificationController extends ResourceController
{
    protected $notificationModel;
    protected $documentModel;
    protected $userModel;
    protected $borModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->documentModel = new DocumentModel();
        $this->userModel = new UserModel();
        $this->borModel = new BORMemberModel();
    }

    /**
     * Get authenticated user ID from request
     */
    private function getUserId()
    {
        $user = $this->request->user ?? null;
        return (int) ($user['user_id'] ?? 0);
    }

    /**
     * Get all notifications or filter by status
     * GET /api/notifications?filter=all|unread
     */
    public function index()
    {
        $filter = $this->request->getVar('filter') ?? 'all';
        $userId = $this->getUserId();

        if ($userId <= 0) {
            return $this->failUnauthorized('Not authenticated');
        }

        $notifications = $this->notificationModel->getUserNotifications($userId, $filter);
        
        // Decode JSON data field and format timestamps to ISO 8601 with timezone
        foreach ($notifications as &$notification) {
            if (!empty($notification['data'])) {
                $notification['data'] = json_decode($notification['data'], true);
            }
            // Ensure created_at is in ISO 8601 format with Z suffix for UTC
            if (!empty($notification['created_at'])) {
                $timestamp = strtotime($notification['created_at']);
                if ($timestamp !== false) {
                    $notification['created_at'] = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
                }
            }
        }

        return $this->respond([
            'notifications' => $notifications,
            'total' => count($notifications),
            'unread_count' => $this->notificationModel->getUnreadCount($userId),
        ]);
    }

    /**
     * Get unread count
     * GET /api/notifications/unread/count
     */
    public function unreadCount()
    {
        $userId = $this->getUserId();

        if ($userId <= 0) {
            return $this->failUnauthorized('Not authenticated');
        }

        $count = $this->notificationModel->getUnreadCount($userId);

        return $this->respond(['unread_count' => $count]);
    }

    /**
     * Mark notification as read
     * PUT /api/notifications/:id/read
     */
    public function markRead($id = null)
    {
        $userId = $this->getUserId();

        if ($userId <= 0) {
            return $this->failUnauthorized('Not authenticated');
        }

        $notification = $this->notificationModel->find($id);

        if (!$notification || $notification['user_id'] != $userId) {
            return $this->failNotFound('Notification not found');
        }

        $this->notificationModel->markAsRead($id, $userId);

        return $this->respond(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     * PUT /api/notifications/read-all
     */
    public function markAllRead()
    {
        $userId = $this->getUserId();

        if ($userId <= 0) {
            return $this->failUnauthorized('Not authenticated');
        }

        $this->notificationModel->markAllAsRead($userId);

        return $this->respond(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete a notification
     * DELETE /api/notifications/:id
     */
    public function delete($id = null)
    {
        $userId = $this->getUserId();

        if ($userId <= 0) {
            return $this->failUnauthorized('Not authenticated');
        }

        $notification = $this->notificationModel->find($id);

        if (!$notification || $notification['user_id'] != $userId) {
            return $this->failNotFound('Notification not found');
        }

        $this->notificationModel->deleteNotification($id, $userId);

        return $this->respondDeleted(['message' => 'Notification deleted']);
    }

    /**
     * Delete all notifications
     * DELETE /api/notifications
     */
    public function deleteAll()
    {
        $userId = $this->getUserId();

        if ($userId <= 0) {
            return $this->failUnauthorized('Not authenticated');
        }

        $this->notificationModel->where('user_id', $userId)->delete();

        return $this->respondDeleted(['message' => 'All notifications deleted']);
    }

    /**
     * Check for approaching deadlines and create notifications
     * Can be called by a scheduled task/cron job
     * GET /api/notifications/check-deadlines (admin only)
     */
    public function checkDeadlines()
    {
        // This should be called by a scheduled task, not directly by users
        // In production, add authorization check to ensure it's called only by system
        
        try {
            $now = time();
            $threeDaysFromNow = $now + (3 * 24 * 60 * 60); // 3 days
            
            // Find documents with reply deadlines approaching
            $db = \Config\Database::connect();
            $documents = $db->query(
                "SELECT * FROM documents 
                 WHERE status IN ('pending', 'active') 
                 AND reply_deadline_at IS NOT NULL 
                 AND reply_deadline_at > FROM_UNIXTIME(?) 
                 AND reply_deadline_at <= FROM_UNIXTIME(?) 
                 AND id NOT IN (
                    SELECT DISTINCT document_id FROM notifications 
                    WHERE type = 'document_deadline'
                 )",
                [$now, $threeDaysFromNow]
            )->getResultArray();
            
            $createdCount = 0;
            
            foreach ($documents as $doc) {
                // Get recipient users
                $recipientType = $doc['recipient_type'] ?? '';
                $recipientUsers = [];
                
                if ($recipientType === 'bor') {
                    $recipientUsers = $this->borModel
                        ->select('bor_members.user_id')
                        ->join('users', 'users.id = bor_members.user_id')
                        ->where('users.is_active', 1)
                        ->findAll();
                    $recipientUsers = array_column($recipientUsers, 'user_id');
                } elseif (in_array($recipientType, ['uac', 'uadmin'], true)) {
                    $recipientUsers = $this->userModel
                        ->select('id')
                        ->where('user_type', $recipientType)
                        ->where('is_active', 1)
                        ->findAll();
                    $recipientUsers = array_column($recipientUsers, 'id');
                }
                
                // Create notification for each recipient
                foreach ($recipientUsers as $userId) {
                    $daysLeft = ceil((strtotime($doc['reply_deadline_at']) - time()) / 86400);
                    
                    $this->notificationModel->createNotification(
                        $userId,
                        'document_deadline',
                        'Deadline Approaching',
                        '"' . ($doc['title'] ?? 'Document') . '" has ' . (int)$daysLeft . ' days remaining. Reply deadline: ' . date('M d, Y', strtotime($doc['reply_deadline_at'])),
                        [
                            'document_id' => $doc['id'],
                            'document_title' => $doc['title'] ?? '',
                            'document_number' => $doc['document_number'] ?? '',
                            'deadline' => $doc['reply_deadline_at'],
                            'days_left' => (int)$daysLeft,
                        ],
                        '/recipient/documents/' . $doc['id']
                    );
                    
                    $createdCount++;
                }
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Checked for deadline notifications',
                'notifications_created' => $createdCount,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error checking document deadlines: ' . $e->getMessage());
            return $this->respond(
                ['error' => 'Failed to check deadlines'],
                500
            );
        }
    }
}
