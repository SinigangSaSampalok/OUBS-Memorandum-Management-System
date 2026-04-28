<?php
// Simple direct database connection
$db = new mysqli('localhost', 'root', '', 'oubs_system');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== Database Check ===\n";

// Check all users
$result = $db->query("SELECT id, username, user_type FROM users ORDER BY id");
$users = $result->fetch_all(MYSQLI_ASSOC);
echo "Total users: " . count($users) . "\n";
foreach ($users as $u) {
    echo "  ID: {$u['id']}, Username: {$u['username']}, Type: {$u['user_type']}\n";
}

// Check notifications
echo "\n=== Notifications ===\n";
$result = $db->query("SELECT COUNT(*) as cnt FROM notifications");
$totalNotifs = $result->fetch_assoc()['cnt'];
echo "Total notifications: " . $totalNotifs . "\n";

if ($totalNotifs > 0) {
    $result = $db->query("SELECT id, user_id, type, title, created_at FROM notifications ORDER BY id DESC LIMIT 10");
    $notifs = $result->fetch_all(MYSQLI_ASSOC);
    echo "\nRecent notifications:\n";
    foreach ($notifs as $n) {
        echo "  ID: {$n['id']}, User: {$n['user_id']}, Type: {$n['type']}, Title: {$n['title']}\n";
    }
}

// Check for user 1 (OUBS) specifically
echo "\n=== User 1 (OUBS) Notifications ===\n";
$result = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = 1");
$oubsNotifs = $result->fetch_assoc()['cnt'];
echo "Notifications for user 1: " . $oubsNotifs . "\n";

$db->close();
