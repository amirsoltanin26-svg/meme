<?php
/** Database configuration via Railway MySQL variables or DB_* variables. */
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306');
$dbname = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'chatbot');
$username = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$password = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: '');

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check the service database environment variables.');
}

require_once __DIR__ . '/db_init.php';
try {
    ensureTables($pdo);
} catch (Throwable $e) {
    error_log('ensureTables failed: ' . $e->getMessage());
}
