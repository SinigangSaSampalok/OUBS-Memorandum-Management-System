<?php

namespace App\Models;

use CodeIgniter\Model;

class BORMemberModel extends Model
{
    protected $table = 'bor_members';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'member_number', 'committee_role'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    protected $validationRules = [
        'user_id' => 'required|numeric|is_unique[bor_members.user_id]',
        'member_number' => 'required|numeric|greater_than[0]|less_than[13]',
        'committee_role' => 'permit_empty|max_length[100]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get BOR member by user ID
     */
    public function getByUserId($userId)
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Get all BOR members with user details
     */
    public function getAllWithUserDetails()
    {
        return $this->select('bor_members.*, users.full_name, users.position, users.username, users.password, users.is_document_reviewer')
                    ->join('users', 'users.id = bor_members.user_id')
                    ->where('users.is_active', true)
                    ->orderBy('bor_members.member_number', 'ASC')
                    ->findAll();
    }

    /**
     * Get total active BOR members
     */
    public function getTotalActiveMembers()
    {
        return $this->join('users', 'users.id = bor_members.user_id')
                    ->where('users.is_active', true)
                    ->countAllResults();
    }
}
