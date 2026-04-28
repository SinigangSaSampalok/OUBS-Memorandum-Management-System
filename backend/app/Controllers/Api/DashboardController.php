<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class DashboardController extends BaseController
{
    use ResponseTrait;

    public function stats()
    {
        $user = $this->request->user ?? null;
        if (!is_array($user) || empty($user['user_type'])) {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }

        $db = db_connect();
        $userType = (string) $user['user_type'];
        $userId = (int) ($user['user_id'] ?? 0);

        if ($userType === 'oubs') {
            $uploadedDocuments = (int) $db->table('documents')
                ->where('file_path IS NOT NULL', null, false)
                ->where("TRIM(file_path) <> ''", null, false)
                ->countAllResults();

            $totalDocuments = (int) $db->table('documents')->countAllResults();
            $signedReplies = (int) $db->table('reply_slips')->countAllResults();
            $approvedReplies = (int) $db->table('reply_slips')->where('action', 'approve')->countAllResults();
            $disapprovedReplies = (int) $db->table('reply_slips')->where('action', 'disapprove')->countAllResults();
            $pendingDocuments = (int) $db->table('documents')->where('status', 'pending')->countAllResults();
            $completedDocuments = (int) $db->table('documents')->where('status', 'completed')->countAllResults();
            $closedDocuments = (int) $db->table('documents')->where('status', 'closed')->countAllResults();

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'scope' => 'oubs',
                    'uploaded_documents' => $uploadedDocuments,
                    'total_documents' => $totalDocuments,
                    'signed_replies' => $signedReplies,
                    'approved_replies' => $approvedReplies,
                    'disapproved_replies' => $disapprovedReplies,
                    'pending_documents' => $pendingDocuments,
                    'completed_documents' => $completedDocuments,
                    'closed_documents' => $closedDocuments,
                ],
            ]);
        }

        $assignedQuery = $db->table('documents')
            ->where('recipient_type', $userType);

        // Document review is only for BOR documents.
        if ($userType === 'bor') {
            $assignedQuery->where('review_status', 'allowed');
        }

        $assignedDocuments = (int) $assignedQuery->countAllResults();

        $signedDocuments = (int) $db->table('reply_slips')
            ->where('user_id', $userId)
            ->select('COUNT(DISTINCT document_id) AS total', false)
            ->get()
            ->getRow('total');

        $approvedResponses = (int) $db->table('reply_slips')
            ->where('user_id', $userId)
            ->where('action', 'approve')
            ->countAllResults();

        $disapprovedResponses = (int) $db->table('reply_slips')
            ->where('user_id', $userId)
            ->where('action', 'disapprove')
            ->countAllResults();

        $pendingResponses = max(0, $assignedDocuments - $signedDocuments);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'scope' => 'recipient',
                'assigned_documents' => $assignedDocuments,
                'signed_documents' => $signedDocuments,
                'pending_responses' => $pendingResponses,
                'approved_responses' => $approvedResponses,
                'disapproved_responses' => $disapprovedResponses,
            ],
        ]);
    }

    public function recentActivities()
    {
        $user = $this->request->user ?? null;
        if (!is_array($user) || empty($user['user_type'])) {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }

        $db = db_connect();
        $userType = (string) $user['user_type'];
        $userId = (int) ($user['user_id'] ?? 0);

        if ($userType === 'oubs') {
            $documentActivities = $db->table('documents d')
                ->select("d.id, d.title, d.document_number, d.created_at, 'document_uploaded' AS activity_type", false)
                ->orderBy('d.created_at', 'DESC')
                ->limit(8)
                ->get()
                ->getResultArray();

            $replyActivities = $db->table('reply_slips r')
                ->select("r.id, d.title, d.document_number, r.date_signed AS created_at, CONCAT('reply_', r.action) AS activity_type", false)
                ->join('documents d', 'd.id = r.document_id', 'left')
                ->orderBy('r.date_signed', 'DESC')
                ->limit(8)
                ->get()
                ->getResultArray();

            $activities = array_merge($documentActivities, $replyActivities);
        } else {
            $activities = $db->table('reply_slips r')
                ->select("r.id, d.title, d.document_number, r.date_signed AS created_at, CONCAT('reply_', r.action) AS activity_type", false)
                ->join('documents d', 'd.id = r.document_id', 'left')
                ->where('r.user_id', $userId)
                ->orderBy('r.date_signed', 'DESC')
                ->limit(12)
                ->get()
                ->getResultArray();
        }

        usort($activities, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        $activities = array_slice($activities, 0, 10);

        return $this->respond([
            'status' => 'success',
            'data' => $activities,
        ]);
    }
}
