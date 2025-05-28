<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Mysql@@1123');
define('DB_NAME', 'cost1');
define('DB_PORT', '3307');

// Admin credentials
define('ADMIN_USERNAME', 'Dhruv');
define('ADMIN_PASSWORD', '160902');

// Create database connection
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check logs or contact support.");
        }
    }
    return $db;
}

// Password verification
function verifyAdminPassword($password) {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT password FROM admin_settings ORDER BY id LIMIT 1");
        $hash = $stmt->fetchColumn();
        
        if ($hash) {
            return password_verify($password, $hash);
        } elseif (defined('ADMIN_PASSWORD') && ADMIN_PASSWORD !== '') {
            return $password === ADMIN_PASSWORD;
        }
    } catch (PDOException $e) {
        error_log("Admin password verification DB error: " . $e->getMessage());
        return $password === ADMIN_PASSWORD;
    }
    return false;
}

// Update admin password
function updateAdminPassword($new_password) {
    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $db = getDB();
    try {
        $stmtCheck = $db->query("SELECT id FROM admin_settings ORDER BY id LIMIT 1");
        $existing_id = $stmtCheck->fetchColumn();

        if ($existing_id) {
            $stmt = $db->prepare("UPDATE admin_settings SET password = :password WHERE id = :id");
            return $stmt->execute([':password' => $hash, ':id' => $existing_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO admin_settings (password) VALUES (:password)");
            return $stmt->execute([':password' => $hash]);
        }
    } catch (PDOException $e) {
        error_log("Admin password update DB error: " . $e->getMessage());
        return false;
    }
}

// UI Styles
function getCSS() {
    return '
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: "Inter", system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .date-filter {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            font-size: 0.95em;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        }

        .data-table th, .data-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .data-table td::before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
                margin-right: 1rem;
            }
        }
    </style>';
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Authentication check
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
}
?>