<?php
// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'eduportal',
    'charset' => 'utf8mb4'
];

// Error reporting for development
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

try {
    // Connect to MySQL
    $conn = new mysqli(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database']
    );

    if ($conn->connect_errno) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Set charset
    $conn->set_charset($dbConfig['charset']);

    // Set timezone (optional)
    $conn->query("SET time_zone = '+00:00'");

    // Register shutdown to close the connection automatically
    register_shutdown_function(function () use (&$conn) {
        if ($conn instanceof mysqli && $conn->ping()) {
            $conn->close();
        }
    });
} catch (Exception $e) {
    error_log("[".date('Y-m-d H:i:s')."] DB Error: " . $e->getMessage() . "\n", 3, __DIR__ . "/../error.log");
    die("<h2>Service Temporarily Unavailable</h2>
         <p>Technical issue encountered. Please try again later.</p>");
}
?>
