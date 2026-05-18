<?php
require_once 'db.php';

$stmt = db()->query("SELECT * FROM appointments");
$rows = $stmt->fetchAll();

echo '<pre>';
print_r($rows);
echo '</pre>';