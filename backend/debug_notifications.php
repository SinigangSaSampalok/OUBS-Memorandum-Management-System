<?php
require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;

try {
    $config = new Database();
    $db = $config->connect('default');
    
    echo "=== Checking Notifications ===\n";
    
    $count = $db->query("SELECT COUNT(*) as cnt FROM notifications")->getRow()->cnt;
    echo "Total notifications: " . $count . "\n";
    
    if ($count > 0) {
        $recent = $db->query("SELECT id, user_id, type, title, read_at, created_at FROM notifications ORDER BY created_at DESC LIMIT 5")->getResultArray();
        echo "\nLatest 5 notifications:\n";
        foreach ($recent as $n) {
            $read = $n['read_at'] ? 'READ' : 'UNREAD';
            echo "  ID: {$n['id']}, User: {$n['user_id']}, Type: {$n['type']}, Status: $read\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
