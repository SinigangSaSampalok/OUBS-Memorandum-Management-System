<?php

namespace Config;

$routes = Services::routes();

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

// Handle ALL preflight OPTIONS requests - must be BEFORE the api group
$routes->options('api/(:any)', 'CorsController::preflight');

// API Routes
$routes->group('api', function($routes) {
    
    // CSRF Token endpoint (must be before other routes)
    $routes->get('auth/csrf-token', 'Api\AuthController::getCsrfToken');
    
    // Public routes - All state-changing operations require CSRF protection
    $routes->post('auth/login', 'Api\AuthController::login', ['filter' => 'csrf']);
    $routes->post('auth/recipient-login', 'Api\AuthController::recipientLogin', ['filter' => 'csrf']);
    $routes->post('auth/recipient-status', 'Api\AuthController::recipientStatus', ['filter' => 'csrf']);
    $routes->post('auth/set-recipient-password', 'Api\AuthController::setRecipientPassword', ['filter' => 'csrf']);
    $routes->post('auth/request-password-reset', 'Api\PasswordResetController::requestReset', ['filter' => 'csrf']);
    
    // Member endpoints (public - needed for login dropdown)
    $routes->get('members/bor', 'Api\MemberController::getBORMembers');
    $routes->get('members/(:segment)', 'Api\MemberController::getMembersByType/$1');
    
    // Protected routes
    $routes->group('', ['filter' => 'auth'], function($routes) {
        
        // User Management
        $routes->get('users/profile', 'Api\UserController::profile');
        $routes->put('users/update-signature', 'Api\UserController::updateSignature', ['filter' => 'csrf']);
        $routes->get('auth/login-logs', 'Api\AuthController::loginLogs');
        $routes->post('users', 'Api\MemberController::create', ['filter' => 'csrf']);
        $routes->put('users/(:num)', 'Api\MemberController::update/$1', ['filter' => 'csrf']);
        $routes->delete('users/(:num)', 'Api\MemberController::delete/$1', ['filter' => 'csrf']);
        $routes->get('college-campuses', 'Api\MemberController::getCollegeCampuses');
        $routes->get('password-reset-requests', 'Api\PasswordResetController::index');
        $routes->put('password-reset-requests/(:num)', 'Api\PasswordResetController::review/$1', ['filter' => 'csrf']);
        
        // Documents
        $routes->get('documents', 'Api\DocumentController::index');
        $routes->get('documents/(:num)', 'Api\DocumentController::show/$1');
        $routes->post('documents', 'Api\DocumentController::create', ['filter' => 'csrf']);
        $routes->put('documents/(:num)', 'Api\DocumentController::update/$1', ['filter' => 'csrf']);
        $routes->delete('documents/(:num)', 'Api\DocumentController::delete/$1', ['filter' => 'csrf']);
        $routes->post('documents/upload', 'Api\DocumentController::upload', ['filter' => 'csrf']);
        $routes->get('documents/download/(:num)', 'Api\DocumentController::download/$1');
        $routes->get('documents/my-documents', 'Api\DocumentController::myDocuments');
        $routes->get('documents/recipient-type/(:any)', 'Api\DocumentController::byRecipientType/$1');

        // Document Review (BOR Reviewer)
        $routes->get('document-reviews', 'Api\DocumentReviewController::index');
        $routes->put('document-reviews/(:num)', 'Api\DocumentReviewController::update/$1', ['filter' => 'csrf']);
        $routes->get('document-reviews/(:num)/letter', 'Api\DocumentReviewController::letter/$1');
        $routes->get('document-reviewer', 'Api\DocumentReviewController::reviewer');
        $routes->put('document-reviewer', 'Api\DocumentReviewController::setReviewer', ['filter' => 'csrf']);
        $routes->delete('document-reviewer', 'Api\DocumentReviewController::unsetReviewer', ['filter' => 'csrf']);
        
        // Reply Slips
        $routes->post('reply-slips', 'Api\ReplySlipController::create', ['filter' => 'csrf']);
        $routes->get('reply-slips/document/(:num)', 'Api\ReplySlipController::byDocument/$1');
        $routes->get('reply-slips/my-replies', 'Api\ReplySlipController::myReplies');
        $routes->get('reply-slips/download/(:num)', 'Api\ReplySlipController::download/$1');
        
        // Summary of Actions
        $routes->get('summary/document/(:num)', 'Api\SummaryController::byDocument/$1');
        $routes->get('summary/download/(:num)', 'Api\SummaryController::download/$1');
        
        // Dashboard Stats
        $routes->get('dashboard/stats', 'Api\DashboardController::stats');
        $routes->get('dashboard/recent-activities', 'Api\DashboardController::recentActivities');
        
        // Notifications
        $routes->get('notifications', 'Api\NotificationController::index');
        $routes->get('notifications/unread/count', 'Api\NotificationController::unreadCount');
        $routes->get('notifications/check-deadlines', 'Api\NotificationController::checkDeadlines');
        $routes->put('notifications/(:num)/read', 'Api\NotificationController::markRead/$1', ['filter' => 'csrf']);
        $routes->put('notifications/read-all', 'Api\NotificationController::markAllRead', ['filter' => 'csrf']);
        $routes->delete('notifications/(:num)', 'Api\NotificationController::delete/$1', ['filter' => 'csrf']);
        $routes->delete('notifications', 'Api\NotificationController::deleteAll', ['filter' => 'csrf']);
    });
});

$routes->get('/', 'Home::index');
