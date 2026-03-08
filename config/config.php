<?php

// Define base URL - make sure this matches your actual project path
define('BASE_URL', 'http://localhost/ztorespot_sales_team_panel/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

define('APP_NAME', 'Ztorespot Sales Team Panel');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ztorespot_sales_panel');
define('DB_USER', 'root');
define('DB_PASS', '');

function db()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "msg" => "Database connection failed. Please try again later."
            ]);
            exit;
        }
    }

    return $pdo;
}
