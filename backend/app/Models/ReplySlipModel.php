<?php

namespace App\Models;

use CodeIgniter\Model;

class ReplySlipModel extends Model
{
    protected $table = 'reply_slips';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'document_id', 'user_id', 'action', 'remarks', 
        'date_signed', 'signature_image'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [
        'document_id' => 'required|numeric',
        'user_id' => 'required|numeric',
        'action' => 'required|in_list[approve,disapprove]',
        'remarks' => 'permit_empty|string',
        'signature_image' => 'required'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get reply slips by document
     */
    public function getByDocument($documentId)
    {
        return $this->select('reply_slips.*, users.full_name, users.position')
                    ->join('users', 'users.id = reply_slips.user_id')
                    ->where('document_id', $documentId)
                    ->orderBy('date_signed', 'ASC')
                    ->findAll();
    }

    /**
     * Get reply slips by user
     */
    public function getByUser($userId)
    {
        return $this->select('reply_slips.*, documents.document_number, documents.title, documents.recipient_type')
                    ->join('documents', 'documents.id = reply_slips.document_id')
                    ->where('user_id', $userId)
                    ->orderBy('reply_slips.created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Check if user has replied to document
     */
    public function hasUserReplied($documentId, $userId)
    {
        return $this->where('document_id', $documentId)
                    ->where('user_id', $userId)
                    ->countAllResults() > 0;
    }

    /**
     * Get reply statistics for a document
     */
    public function getDocumentStats($documentId)
    {
        $total = $this->where('document_id', $documentId)->countAllResults();
        $approved = $this->where('document_id', $documentId)
                         ->where('action', 'approve')
                         ->countAllResults();
        $disapproved = $this->where('document_id', $documentId)
                            ->where('action', 'disapprove')
                            ->countAllResults();

        return [
            'total' => $total,
            'approved' => $approved,
            'disapproved' => $disapproved,
            'approval_rate' => $total > 0 ? ($approved / $total) * 100 : 0
        ];
    }

    /**
     * Get reply slip with full details
     */
    public function getWithDetails($id)
    {
        return $this->select('reply_slips.*, documents.document_number, documents.title, users.full_name, users.position')
                    ->join('documents', 'documents.id = reply_slips.document_id')
                    ->join('users', 'users.id = reply_slips.user_id')
                    ->where('reply_slips.id', $id)
                    ->first();
    }
}
