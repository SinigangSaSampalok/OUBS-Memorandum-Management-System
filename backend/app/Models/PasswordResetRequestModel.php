<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetRequestModel extends Model
{
    protected $table = 'password_reset_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewer_note',
        'reviewed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getLatestByUserId($userId)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function getLatestPendingByUserId($userId)
    {
        return $this->where('user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
