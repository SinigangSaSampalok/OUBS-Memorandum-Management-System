<?php
require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;

$config = new Database();
$db = $config->connect('default');

echo "=== Checking Notifications ===\n";

try {
    $count = $db->query("SELECT COUNT(*) as cnt FROM notifications")->getRow()->cnt;
    echo "Total notifications: " . $count . "\n";
    
    if ($count > 0) {
        $recent = $db->query("SELECT id, user_id, type, title FROM notifications ORDER BY id DESC LIMIT 10")->getResultArray();
        echo "\nRecent notifications:\n";
        foreach ($recent as $n) {
            echo "  ID: {$n['id']}, User: {$n['user_id']}, Type: {$n['type']}, Title: {$n['title']}\n";
        }
    } else {
        echo "No notifications found.\n";
    }
    
    // Check users
    echo "\n=== Checking Users ===\n";
    $users = $db->query("SELECT id, username, user_type FROM users LIMIT 20")->getResultArray();
    echo "Total users fetched: " . count($users) . "\n";
    foreach ($users as $u) {
        echo "  ID: {$u['id']}, Username: {$u['username']}, Type: {$u['user_type']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
