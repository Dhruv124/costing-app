<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

session_start();

if (!isAdmin()) {
    redirect('admin.php');
}

$id = $_GET['id'] ?? 0;
$db = getDB();

// Verify entry exists
$entry = $db->query("SELECT id FROM export_entries WHERE id = " . (int)$id)->fetch();

if ($entry) {
    $stmt = $db->prepare("DELETE FROM export_entries WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success_message'] = "Entry deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete entry.";
    }
} else {
    $_SESSION['error_message'] = "Entry not found.";
}

redirect('admin.php');
?>