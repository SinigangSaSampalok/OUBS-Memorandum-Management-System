<?php

namespace App\Models;

use CodeIgniter\Model;

class SummaryActionModel extends Model
{
    protected $table = 'summary_actions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'document_id', 'user_id', 'full_name', 'position', 
        'action', 'remarks', 'date_signed'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [
        'document_id' => 'required|numeric',
        'user_id' => 'required|numeric',
        'full_name' => 'required|min_length[5]|max_length[255]',
        'action' => 'required|in_list[approve,disapprove]',
        'date_signed' => 'required|valid_date'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get summary actions by document
     */
    public function getByDocument($documentId)
    {
        return $this->where('document_id', $documentId)
                    ->orderBy('date_signed', 'ASC')
                    ->findAll();
    }

    /**
     * Get summary actions by user
     */
    public function getByUser($userId)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('date_signed', 'DESC')
                    ->findAll();
    }

    /**
     * Get summary statistics for a document
     */
    public function getDocumentSummary($documentId)
    {
        $actions = $this->where('document_id', $documentId)->findAll();
        
        $summary = [
            'total_actions' => count($actions),
            'approvals' => 0,
            'disapprovals' => 0,
            'actions' => $actions
        ];

        foreach ($actions as $action) {
            if ($action['action'] === 'approve') {
                $summary['approvals']++;
            } else {
                $summary['disapprovals']++;
            }
        }

        return $summary;
    }

    /**
     * Record an action in summary
     */
    public function recordAction($data)
    {
        return $this->insert($data);
    }
}
