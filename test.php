<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=cost1', 'root', 'Mysql@@1123');
    echo "Connected successfully!";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}