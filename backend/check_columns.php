<?php
require 'preload.php';
$db = \Config\Database::connect();
$fields = $db->getFieldData('documents');
echo "Documents table columns:\n";
foreach($fields as $f) {
    echo "  {$f->name} - {$f->type}\n";
}