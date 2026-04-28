<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['user_id', 'type', 'title', 'message', 'data', 'action_url', 'read_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';
    protected $validationRules = [
        'user_id' => 'required|integer',
        'type' => 'required|string|max_length[50]',
        'title' => 'required|string|max_length[255]',
        'message' => 'required|string',
    ];

    /**
     * Get all notifications for a user
     */
    public function getUserNotifications($userId, $filter = 'all')
    {
        $builder = $this->builder();
        $builder->where('user_id', $userId);

        if ($filter === 'unread') {
            $builder->where('read_at IS NULL');
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount($userId)
    {
        return $this->builder()
            ->where('user_id', $userId)
            ->where('read_at IS NULL')
            ->countAllResults();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId)
    {
        return $this->builder()
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId)
    {
        return $this->builder()
            ->where('user_id', $userId)
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($notificationId, $userId)
    {
        return $this->where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Create a notification
     */
    public function createNotification($userId, $type, $title, $message, $data = null, $actionUrl = null)
    {
        return $this->insert([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'action_url' => $actionUrl,
        ]);
    }
}
