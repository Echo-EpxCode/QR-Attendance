<?php
$host = 'localhost';
$dbname = 'Qr_Code';
$username = 'root';
$password = '';

try {
    // Step 1: Connect without database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 2: Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");

    // Step 3: Connect to our database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 4: Auto-create table with full schema
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            student_id VARCHAR(50) UNIQUE NOT NULL,
            qr_token VARCHAR(100) UNIQUE NOT NULL,
            status ENUM('REGISTERED','IN','OUT') DEFAULT 'REGISTERED',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_qr_token (qr_token),
            INDEX idx_student_id (student_id),
            INDEX idx_status (status)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($createTableSQL);

    // Step 5: Ensure indexes exist (safe to run multiple times)
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_qr_token ON students(qr_token)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_student_id ON students(student_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_status ON students(status)");

    // Success message (remove in production)
    if (!isset($_GET['quiet'])) {
        error_log("✅ Database 'attendance_system' initialized successfully!");
    }
} catch (PDOException $e) {
    // Show friendly error for development
    die("Database connection failed: " . $e->getMessage() . "<br>
     Make sure MySQL is running and credentials are correct in config.php");
}
