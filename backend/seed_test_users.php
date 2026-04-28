<?php
require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;

$config = new Database();
$db = $config->connect('default');

echo "=== Creating Test Users ===\n";

// Create test users
$users = [
  ['username' => 'bor01', 'full_name' => 'Dr. Maria Santos', 'email' => 'maria@university.edu', 'user_type' => 'bor', 'position' => 'Chairperson'],
  ['username' => 'bor02', 'full_name' => 'Engr. Juan Dela Cruz', 'email' => 'juan@university.edu', 'user_type' => 'bor', 'position' => 'Member'],
  ['username' => 'uac01', 'full_name' => 'Dr. Antonio Rivera', 'email' => 'antonio@university.edu', 'user_type' => 'uac', 'position' => 'Dean'],
  ['username' => 'uac02', 'full_name' => 'Prof. Ana Garcia', 'email' => 'ana@university.edu', 'user_type' => 'uac', 'position' => 'Faculty'],
  ['username' => 'uadmin01', 'full_name' => 'Dr. Pedro Lopez', 'email' => 'pedro@university.edu', 'user_type' => 'uadmin', 'position' => 'Director'],
  ['username' => 'uadmin02', 'full_name' => 'Ms. Rosa Fernandez', 'email' => 'rosa@university.edu', 'user_type' => 'uadmin', 'position' => 'Manager'],
];

$password = password_hash('password', PASSWORD_DEFAULT);

foreach($users as $user) {
  $exists = $db->table('users')->where('username', $user['username'])->get()->getNumRows();
  if (!$exists) {
    $db->table('users')->insert(array_merge($user, ['password' => $password, 'is_active' => 1]));
    echo 'Created: ' . $user['username'] . PHP_EOL;
  }
}

echo "\nTest users created successfully!\n";
