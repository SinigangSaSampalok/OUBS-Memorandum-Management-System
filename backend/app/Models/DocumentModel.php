<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'document_number', 'title', 'description', 'file_path', 
        'file_name', 'file_size', 'file_type', 'recipient_type', 
        'allow_replies', 'reply_available_days', 'download_available_days', 'reply_deadline_at',
        'download_deadline_at', 'uploaded_by', 'status',
        'review_status', 'reviewed_by', 'reviewed_at', 'review_note',
        'archived', 'archived_at', 'archived_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'document_number' => 'required|max_length[50]|is_unique[documents.document_number]',
        'title' => 'required|min_length[5]|max_length[500]',
        'description' => 'permit_empty',
        'recipient_type' => 'required|in_list[bor,uac,uadmin]',
        'allow_replies' => 'permit_empty|in_list[0,1]',
        'reply_available_days' => 'permit_empty|integer|greater_than_equal_to[1]',
        'download_available_days' => 'permit_empty|integer|greater_than_equal_to[1]',
        'uploaded_by' => 'required|numeric',
        'status' => 'required|in_list[pending,approved,rejected,partially_approved,completed,closed]',
        'review_status' => 'permit_empty|in_list[pending,allowed,not_allowed]'
    ];

    protected $validationMessages = [
        'document_number' => [
            'required' => 'Document number is required.',
            'max_length' => 'Document number must be 50 characters or fewer.',
            'is_unique' => 'Document number must be unique.',
        ],
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get documents by uploader
     */
    public function getByUploader($userId)
    {
        return $this->where('uploaded_by', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get document with uploader details
     */
    public function getDocumentWithDetails($id)
    {
        return $this->select('documents.*, users.full_name as uploaded_by_name, users.position as uploaded_by_position')
                    ->join('users', 'users.id = documents.uploaded_by')
                    ->where('documents.id', $id)
                    ->first();
    }

    /**
     * Get all documents with uploader details
     */
    public function getAllWithDetails()
    {
        return $this->select('documents.*, users.full_name as uploaded_by_name')
                    ->join('users', 'users.id = documents.uploaded_by')
                    ->orderBy('documents.created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get document statistics
     */
    public function getStats()
    {
        return [
            'total' => $this->countAllResults(),
            'pending' => $this->where('status', 'pending')->countAllResults(),
            'approved' => $this->where('status', 'approved')->countAllResults(),
            'rejected' => $this->where('status', 'rejected')->countAllResults(),
            'partially_approved' => $this->where('status', 'partially_approved')->countAllResults(),
            'completed' => $this->where('status', 'completed')->countAllResults(),
            'closed' => $this->where('status', 'closed')->countAllResults(),
        ];
    }

    /**
     * Update document status
     */
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Archive a document
     */
    public function archive($id, $userId)
    {
        return $this->update($id, [
            'archived' => 1,
            'archived_at' => date('Y-m-d H:i:s'),
            'archived_by' => $userId
        ]);
    }

    /**
     * Restore a document from archive
     */
    public function restore($id)
    {
        return $this->update($id, [
            'archived' => 0,
            'archived_at' => null,
            'archived_by' => null
        ]);
    }

    /**
     * Get archived documents by recipient type
     */
    public function getArchivedByRecipientType($recipientType)
    {
        return $this->where('recipient_type', $recipientType)
                    ->where('archived', 1)
                    ->orderBy('archived_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get all archived documents
     */
    public function getAllArchived()
    {
        return $this->select('documents.*, users.full_name as uploaded_by_name')
                    ->join('users', 'users.id = documents.uploaded_by')
                    ->where('documents.archived', 1)
                    ->orderBy('documents.archived_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get documents by recipient type (excluding archived)
     */
    public function getByRecipientType($recipientType)
    {
        return $this->where('recipient_type', $recipientType)
                    ->where('archived', 0)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
