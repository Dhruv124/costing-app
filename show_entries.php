<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM export_entries ORDER BY entry_date DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($rows);
echo "</pre>";
?>