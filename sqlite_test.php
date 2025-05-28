<?php
$db = new PDO('sqlite:test.db');
$db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, name TEXT)");
echo "SQLite working! Table created.";