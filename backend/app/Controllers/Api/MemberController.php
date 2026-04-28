<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CollegeCampusModel;
use App\Models\UserModel;
use App\Models\BORMemberModel;
use CodeIgniter\API\ResponseTrait;

class MemberController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $borModel;
    protected $collegeCampusModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->borModel = new BORMemberModel();
        $this->collegeCampusModel = new CollegeCampusModel();
    }

    /**
     * Create recipient
     * POST /api/users
     */
    public function create()
    {
        if (($this->request->user['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $json = $this->request->getJSON(true) ?? [];
        $fullName = trim((string) ($json['full_name'] ?? ''));
        $position = trim((string) ($json['position'] ?? ''));
        $userType = trim((string) ($json['user_type'] ?? ''));
        $collegeCampusId = isset($json['college_campus_id']) ? (int) $json['college_campus_id'] : null;

        if ($fullName === '') {
            return $this->respond(['error' => 'Full name is required'], 400);
        }
        if (mb_strlen($fullName) > 255) {
            return $this->respond(['error' => 'Full name is too long'], 400);
        }
        if (mb_strlen($position) > 255) {
            return $this->respond(['error' => 'Position is too long'], 400);
        }
        if (!in_array($userType, ['bor', 'uac', 'uadmin'], true)) {
            return $this->respond(['error' => 'Invalid recipient group'], 400);
        }

        if ($userType === 'uac') {
            if ($collegeCampusId === null || $collegeCampusId <= 0) {
                return $this->respond(['error' => 'College/Campus is required for Academic Council users'], 400);
            }

            $collegeCampus = $this->collegeCampusModel
                ->where('id', $collegeCampusId)
                ->where('is_active', 1)
                ->first();
            if (!$collegeCampus) {
                return $this->respond(['error' => 'Invalid college/campus selection'], 400);
            }
        } else {
            $collegeCampusId = null;
        }

        if ($userType === 'bor') {
            // Count actual BOR member records with active users, not just users with type 'bor'
            $db = \Config\Database::connect();
            $activeBorMemberCount = $db->table('bor_members')
                ->join('users', 'users.id = bor_members.user_id')
                ->where('users.is_active', 1)
                ->countAllResults();
            if ($activeBorMemberCount >= 12) {
                return $this->respond([
                    'error' => 'Cannot add BOR user. The maximum of 12 active BOR members has been reached.'
                ], 400);
            }
        }

        $username = $this->generateUniqueUsername($fullName);

        $db = \Config\Database::connect();
        $db->transStart();

        $userId = $this->userModel
            ->skipValidation(true)
            ->insert([
                'username' => $username,
                'full_name' => $fullName,
                'email' => null,
                // Keep blank so first-time password setup flow can detect "not set".
                'password' => '',
                'user_type' => $userType,
                'position' => $position !== '' ? $position : null,
                'college_campus_id' => $collegeCampusId,
                'signature_image' => null,
                'is_active' => 1,
            ], true);

        if (!$userId) {
            $db->transRollback();
            return $this->respond(['error' => 'Failed to create user'], 500);
        }

        if ($userType === 'bor') {
            $memberNumber = $this->getNextBorMemberNumber();
            if ($memberNumber === null) {
                $db->transRollback();
                return $this->respond([
                    'error' => 'Cannot add BOR user. The maximum of 12 active BOR users has been reached.'
                ], 400);
            }

            $borOk = $db->table('bor_members')->insert([
                'user_id' => (int) $userId,
                'member_number' => $memberNumber,
                'committee_role' => null,
            ]);

            if (!$borOk) {
                $db->transRollback();
                return $this->respond(['error' => 'Failed to create BOR member record'], 500);
            }
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return $this->respond(['error' => 'Failed to create user'], 500);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'User added successfully',
            'data' => [
                'id' => (int) $userId,
                'username' => $username,
                'full_name' => $fullName,
                'user_type' => $userType,
            ],
        ], 201);
    }

    /**
     * Get BOR members list for dropdown
     * GET /api/members/bor
     */
    public function getBORMembers()
    {
        try {
            log_message('info', 'Fetching BOR members list');
            
            // Use the existing method that joins with users table
            $members = $this->borModel->getAllWithUserDetails();

            // Format for dropdown (only need id and full_name)
            $memberList = array_map(function($member) {
                return [
                    'id' => $member['user_id'],
                    'full_name' => $member['full_name'],
                    'position' => $member['position'] ?? '',
                    'member_number' => $member['member_number'],
                    'has_password' => !empty($member['password']),
                    'is_document_reviewer' => (int) ($member['is_document_reviewer'] ?? 0),
                ];
            }, $members);

            log_message('info', 'Found ' . count($memberList) . ' BOR members');

            return $this->respond([
                'status' => 'success',
                'data' => $memberList
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to fetch BOR members: ' . $e->getMessage());
            return $this->fail('Failed to fetch members', 500);
        }
    }

    /**
     * Get UAC or UAdmin members list for dropdown
     * GET /api/members/{type}
     */
    public function getMembersByType($type)
    {
        try {
            log_message('info', "Fetching members for type: {$type}");
            
            // Validate type
            $allowedTypes = ['uac', 'uadmin'];
            if (!in_array($type, $allowedTypes)) {
                return $this->failValidationErrors('Invalid user type');
            }

            $members = $this->userModel
                ->select('users.*, college_campuses.name as college_campus_name, college_campuses.type as college_campus_type')
                ->join('college_campuses', 'college_campuses.id = users.college_campus_id', 'left')
                ->where('users.user_type', $type)
                ->where('users.is_active', 1)
                ->orderBy('users.full_name', 'ASC')
                ->findAll();

            // Return only necessary fields (remove password)
            $memberList = array_map(function($member) {
                return [
                    'id' => $member['id'],
                    'full_name' => $member['full_name'],
                    'user_type' => $member['user_type'],
                    'position' => $member['position'] ?? '',
                    'college_campus_id' => $member['college_campus_id'] ?? null,
                    'college_campus_name' => $member['college_campus_name'] ?? null,
                    'college_campus_type' => $member['college_campus_type'] ?? null,
                    'has_password' => !empty($member['password'])
                ];
            }, $members);

            log_message('info', "Found " . count($memberList) . " members for type {$type}");

            return $this->respond([
                'status' => 'success',
                'data' => $memberList
            ]);
        } catch (\Exception $e) {
            log_message('error', "Failed to fetch {$type} members: " . $e->getMessage());
            return $this->fail('Failed to fetch members', 500);
        }
    }

    /**
     * Update recipient details
     * PUT /api/users/{id}
     */
    public function update($id)
    {
        if (($this->request->user['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $user = $this->userModel->find($id);
        if (!$user || ($user['user_type'] ?? '') === 'oubs') {
            return $this->respond(['error' => 'User not found'], 404);
        }

        $json = $this->request->getJSON(true) ?? [];
        $fullName = trim((string) ($json['full_name'] ?? ''));
        $position = trim((string) ($json['position'] ?? ''));
        $collegeCampusId = isset($json['college_campus_id']) ? (int) $json['college_campus_id'] : null;

        if ($fullName === '') {
            return $this->respond(['error' => 'Full name is required'], 400);
        }

        if (mb_strlen($fullName) > 255) {
            return $this->respond(['error' => 'Full name is too long'], 400);
        }

        if (mb_strlen($position) > 255) {
            return $this->respond(['error' => 'Position is too long'], 400);
        }

        if (($user['user_type'] ?? '') === 'uac') {
            if ($collegeCampusId === null || $collegeCampusId <= 0) {
                return $this->respond(['error' => 'College/Campus is required for Academic Council users'], 400);
            }

            $collegeCampus = $this->collegeCampusModel
                ->where('id', $collegeCampusId)
                ->where('is_active', 1)
                ->first();

            if (!$collegeCampus) {
                return $this->respond(['error' => 'Invalid college/campus selection'], 400);
            }
        } else {
            $collegeCampusId = null;
        }

        $ok = $this->userModel->update($id, [
            'full_name' => $fullName,
            'position' => $position !== '' ? $position : null,
            'college_campus_id' => $collegeCampusId,
        ]);

        if (!$ok) {
            return $this->respond(['error' => 'Failed to update user'], 500);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'User updated successfully',
        ]);
    }

    /**
     * Get active college/campus entries
     * GET /api/college-campuses
     */
    public function getCollegeCampuses()
    {
        if (($this->request->user['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $entries = $this->collegeCampusModel
            ->where('is_active', 1)
            ->orderBy('type', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $entries,
        ]);
    }

    /**
     * Deactivate recipient
     * DELETE /api/users/{id}
     */
    public function delete($id)
    {
        if (($this->request->user['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Access denied'], 403);
        }

        $user = $this->userModel->find($id);
        if (!$user || ($user['user_type'] ?? '') === 'oubs') {
            return $this->respond(['error' => 'User not found'], 404);
        }

        $ok = $this->userModel->update($id, [
            'is_active' => 0,
        ]);

        if (!$ok) {
            return $this->respond(['error' => 'Failed to delete user'], 500);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]);
    }

    private function generateUniqueUsername(string $fullName): string
    {
        $base = strtolower((string) preg_replace('/[^a-z0-9]+/', '', $fullName));
        if ($base === '') {
            $base = 'recipient';
        }
        $base = substr($base, 0, 40);

        $candidate = $base;
        $suffix = 1;
        while ($this->userModel->where('username', $candidate)->first()) {
            $suffix++;
            $candidate = substr($base, 0, 40 - strlen((string) $suffix)) . $suffix;
        }

        return $candidate;
    }

    private function getNextBorMemberNumber(): ?int
    {
        $active = $this->borModel
            ->select('bor_members.member_number')
            ->join('users', 'users.id = bor_members.user_id')
            ->where('users.is_active', 1)
            ->orderBy('bor_members.member_number', 'ASC')
            ->findAll();

        $taken = [];
        foreach ($active as $row) {
            $taken[(int) $row['member_number']] = true;
        }

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($taken[$i])) {
                return $i;
            }
        }

        return null;
    }
}
