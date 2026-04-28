<?php
// Bootstrap CodeIgniter
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'vendor' . DIRECTORY_SEPARATOR . 'codeigniter4' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'development');

require_once ROOTPATH . 'vendor/autoload.php';
require_once SYSTEMPATH . 'bootstrap.php';

// Create database connection
$config = new \Config\Database();
$db = $config->connect('default');

echo "=== Database Check ===\n";

// Check all users
$users = $db->query("SELECT id, username, user_type FROM users ORDER BY id")->getResultArray();
echo "Total users: " . count($users) . "\n";
foreach ($users as $u) {
    echo "  ID: {$u['id']}, Username: {$u['username']}, Type: {$u['user_type']}\n";
}

// Check notifications
echo "\n=== Notifications ===\n";
$totalNotifs = $db->query("SELECT COUNT(*) as cnt FROM notifications")->getRow()->cnt;
echo "Total notifications: " . $totalNotifs . "\n";

if ($totalNotifs > 0) {
    $notifs = $db->query("SELECT id, user_id, type, title, created_at FROM notifications ORDER BY id DESC LIMIT 10")->getResultArray();
    echo "\nRecent notifications:\n";
    foreach ($notifs as $n) {
        echo "  ID: {$n['id']}, User: {$n['user_id']}, Type: {$n['type']}, Title: {$n['title']}, Created: {$n['created_at']}\n";
    }
}

// Check for user 1 (OUBS) specifically
echo "\n=== User 1 (OUBS) Notifications ===\n";
$oubsNotifs = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = 1")->getRow()->cnt;
echo "Notifications for user 1: " . $oubsNotifs . "\n";

if ($oubsNotifs > 0) {
    $notifs = $db->query("SELECT id, type, title FROM notifications WHERE user_id = 1 ORDER BY id DESC")->getResultArray();
    foreach ($notifs as $n) {
        echo "  - {$n['type']}: {$n['title']}\n";
    }
}
