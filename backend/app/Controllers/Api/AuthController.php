<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LoginLogModel;
use App\Models\PasswordResetRequestModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
    
class AuthController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $loginLogModel;
    protected $passwordResetRequestModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->loginLogModel = new LoginLogModel();
        $this->passwordResetRequestModel = new PasswordResetRequestModel();
        helper('text');
    }

    public function login()
    {
        $json = $this->request->getJSON(true);
        
        // Debug logging
        log_message('info', 'Login attempt: ' . json_encode($json));
        
        if (empty($json['password'])) {
            return $this->respond(['error' => 'Password is required'], 400);
        }

        // OUBS Login (only password required)
        $user = $this->userModel->where('user_type', 'oubs')->first();

        if (!$user) {
            log_message('error', 'OUBS user not found in database');
            return $this->respond(['error' => 'OUBS account not found. Please run database seeder.'], 401);
        }

        log_message('info', 'OUBS user found: ' . $user['username']);
        
        if (!password_verify($json['password'], $user['password'])) {
            log_message('error', 'Password verification failed for OUBS');
            return $this->respond([
                'error' => 'Invalid password',
                'debug' => ENVIRONMENT === 'development' ? 'Try password: oubs123' : null
            ], 401);
        }

        unset($user['password']);

        $this->loginLogModel->insert([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_type' => $user['user_type'],
            'ip_address' => $this->request->getIPAddress(),
            'logged_in_at' => date('Y-m-d H:i:s'),
        ]);
        
        $token = $this->createToken($user);

        log_message('info', 'OUBS login successful');

        return $this->respond([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function recipientLogin()
    {
        $json = $this->request->getJSON(true);
        
        // Debug logging
        log_message('info', 'Recipient login attempt: ' . json_encode([
            'full_name' => $json['full_name'] ?? 'not provided',
            'user_type' => $json['user_type'] ?? 'not provided'
        ]));
        
        if (empty($json['full_name']) || empty($json['password']) || empty($json['user_type'])) {
            return $this->respond(['error' => 'All fields are required'], 400);
        }

        if (!$this->isValidRecipientType($json['user_type'])) {
            return $this->respond(['error' => 'Invalid user type'], 400);
        }

        // Find user by full name and type
        $user = $this->userModel
            ->select('users.*, college_campuses.name as college_campus_name, college_campuses.type as college_campus_type')
            ->join('college_campuses', 'college_campuses.id = users.college_campus_id', 'left')
            ->where('users.full_name', $json['full_name'])
            ->where('users.user_type', $json['user_type'])
            ->first();

        if (!$user) {
            log_message('error', 'User not found: ' . $json['full_name'] . ' (' . $json['user_type'] . ')');
            
            // Get available users for this type (development only)
            if (ENVIRONMENT === 'development') {
                $availableUsers = $this->userModel
                    ->where('user_type', $json['user_type'])
                    ->select('full_name')
                    ->findAll();
                
                return $this->respond([
                    'error' => 'User not found',
                    'debug' => [
                        'message' => 'Available users for type ' . $json['user_type'],
                        'users' => array_column($availableUsers, 'full_name')
                    ]
                ], 401);
            }
            
            return $this->respond(['error' => 'Invalid credentials'], 401);
        }

        log_message('info', 'User found: ' . $user['username']);

        $pendingRequest = $this->passwordResetRequestModel->getLatestPendingByUserId($user['id']);
        if ($pendingRequest) {
            return $this->respond([
                'error' => 'Password reset request is pending. You cannot log in until the request is approved or rejected.',
                'code' => 'RESET_REQUEST_PENDING'
            ], 403);
        }

        if (empty($user['password'])) {
            return $this->respond([
                'error' => "This user haven't set their password yet.",
                'code' => 'PASSWORD_NOT_SET'
            ], 403);
        }

        if (!password_verify($json['password'], $user['password'])) {
            log_message('error', 'Password verification failed for ' . $user['username']);
            return $this->respond([
                'error' => 'Invalid password',
                'debug' => ENVIRONMENT === 'development' ? 'Default passwords: bor123, uac123, uadmin123' : null
            ], 401);
        }

        unset($user['password']);

        $this->loginLogModel->insert([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_type' => $user['user_type'],
            'ip_address' => $this->request->getIPAddress(),
            'logged_in_at' => date('Y-m-d H:i:s'),
        ]);
        
        $token = $this->createToken($user);

        log_message('info', 'Login successful for ' . $user['username']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function loginLogs()
    {
        $currentUser = $this->request->user ?? null;
        if (!is_array($currentUser) || ($currentUser['user_type'] ?? null) !== 'oubs') {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $logs = $this->loginLogModel
            ->select('login_logs.*, users.full_name, users.position')
            ->join('users', 'users.id = login_logs.user_id', 'left')
            ->orderBy('logged_in_at', 'DESC')
            ->findAll(500);

        return $this->respond([
            'status' => 'success',
            'data' => $logs,
              ]);
    }

    public function recipientStatus()
    {
        $json = $this->request->getJSON(true);

        if (empty($json['full_name']) || empty($json['user_type'])) {
            return $this->respond(['error' => 'Full name and user type are required'], 400);
        }

        if (!$this->isValidRecipientType($json['user_type'])) {
            return $this->respond(['error' => 'Invalid user type'], 400);
        }

        $user = $this->userModel
            ->where('full_name', $json['full_name'])
            ->where('user_type', $json['user_type'])
            ->first();

        if (!$user) {
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'exists' => false,
                    'has_password' => false
                ]
            ]);
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'exists' => true,
                'has_password' => !empty($user['password']),
                'reset_request' => $this->buildResetRequestStatus($user['id'], !empty($user['password']))
            ]
        ]);
    }

    public function setRecipientPassword()
    {
        $json = $this->request->getJSON(true);

        if (empty($json['full_name']) || empty($json['user_type']) || empty($json['password'])) {
            return $this->respond(['error' => 'Full name, user type, and password are required'], 400);
        }

        $validTypes = ['bor', 'uac', 'uadmin'];
        if (!in_array($json['user_type'], $validTypes)) {
            return $this->respond(['error' => 'Invalid user type'], 400);
        }

        if (strlen($json['password']) < 6) {
            return $this->respond(['error' => 'Password must be at least 6 characters'], 400);
        }

        $user = $this->userModel
            ->where('full_name', $json['full_name'])
            ->where('user_type', $json['user_type'])
            ->first();

        if (!$user) {
            return $this->respond(['error' => 'User not found'], 404);
        }

        if (!empty($user['password'])) {
            return $this->respond(['error' => 'Password is already set for this user'], 409);
        }

        $updated = $this->userModel->update($user['id'], [
            'password' => $json['password']
        ]);

        if (!$updated) {
            return $this->respond([
                'error' => 'Failed to set password',
                'details' => $this->userModel->errors()
            ], 500);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Password has been set successfully'
        ]);
    }

    private function createToken($user)
    {
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'user_type' => $user['user_type'],
            'position' => $user['position'],
            'college_campus_id' => $user['college_campus_id'] ?? null,
            'college_campus_name' => $user['college_campus_name'] ?? null,
            'college_campus_type' => $user['college_campus_type'] ?? null,
            'exp' => time() + (8 * 60 * 60) // 8 hours
        ];
        
        return base64_encode(json_encode($payload));
    }

    private function isValidRecipientType($userType)
    {
        return in_array($userType, ['bor', 'uac', 'uadmin'], true);
    }

    private function buildResetRequestStatus($userId, $hasPassword)
    {
        $latest = $this->passwordResetRequestModel->getLatestByUserId($userId);
        if (!$latest) {
            return null;
        }

        // Once password is already set again, approved requests no longer need UI prompts.
        if ($latest['status'] === 'approved' && $hasPassword) {
            return null;
        }

        return [
            'id' => $latest['id'],
            'status' => $latest['status'],
            'reviewer_note' => $latest['reviewer_note'],
            'reviewed_at' => $latest['reviewed_at'],
        ];
    }

    /**
     * Get CSRF Token
     * 
     * Returns the CSRF token for client-side requests
     * This endpoint can be called without authentication
     * The CSRF cookie is automatically set by CodeIgniter's CSRF filter
     */
    public function getCsrfToken()
    {
        // Ensure CSRF protection is initialized
        // This forces CodeIgniter to generate and set the CSRF cookie
        $token = csrf_token();
        $hash = csrf_hash();
        
        // Return both the token name and hash for the frontend
        return $this->respond([
            'status' => 'success',
            'token' => $hash,  // The actual token value to send in X-CSRF-TOKEN header
            'tokenName' => csrf_token(),  // The token field name (for form submissions, if needed)
            'headerName' => 'X-CSRF-TOKEN',
            'cookieName' => config('Security')->cookieName,
        ]);
    }

    /**
     * Debug endpoint - Remove in production
     */
    public function testUsers()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->respond(['error' => 'Not available in production'], 403);
        }

        $users = $this->userModel
            ->select('users.id, users.username, users.full_name, users.user_type, users.position, users.college_campus_id, college_campuses.name as college_campus_name, users.is_active')
            ->join('college_campuses', 'college_campuses.id = users.college_campus_id', 'left')
            ->findAll();

        return $this->respond([
            'total_users' => count($users),
            'users' => $users
        ]);
    }
}
