<?php
$db = new mysqli('localhost', 'root', '', 'oubs_system', 3306);
if ($db->connect_error) {
    echo 'ERR ' . $db->connect_error . PHP_EOL;
    exit(1);
}
$res = $db->query('SELECT id, created_at FROM notifications ORDER BY id DESC LIMIT 5');
if (!$res) {
    echo 'ERR ' . $db->error . PHP_EOL;
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . ' ' . $row['created_at'] . PHP_EOL;
}
