<?php
require_once 'includes/config.php';

try {
    $db = getDB();
    echo "Database connected successfully!";
    
    // Test query
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetch();
    print_r($result);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}