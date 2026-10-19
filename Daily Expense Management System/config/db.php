<?php
// ============================================================
// config/db.php  -  Database connection
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change if your XAMPP MySQL user is different
define('DB_PASS', '');           // Change if you set a MySQL password
define('DB_NAME', 'expense_tracker');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // In production you would log this instead of dying with details
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check config/db.php'
    ]));
}

$conn->set_charset('utf8mb4');
