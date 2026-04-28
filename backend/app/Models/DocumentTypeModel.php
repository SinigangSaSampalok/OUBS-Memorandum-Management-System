<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentTypeModel extends Model
{
    protected $table = 'document_types';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['type_name', 'recipient_type', 'description'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    protected $validationRules = [
        'type_name' => 'required|min_length[3]|max_length[100]',
        'recipient_type' => 'required|in_list[bor,uac,uadmin]',
        'description' => 'permit_empty'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get document types by recipient type
     */
    public function getByRecipientType($recipientType)
    {
        return $this->where('recipient_type', $recipientType)
                    ->orderBy('type_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get all document types grouped by recipient
     */
    public function getAllGrouped()
    {
        $types = $this->orderBy('recipient_type', 'ASC')
                      ->orderBy('type_name', 'ASC')
                      ->findAll();
        
        $grouped = [
            'bor' => [],
            'uac' => [],
            'uadmin' => []
        ];

        foreach ($types as $type) {
            $grouped[$type['recipient_type']][] = $type;
        }

        return $grouped;
    }
}