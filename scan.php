<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$qr_token = trim($input['qr_token'] ?? '');

if (empty($qr_token)) {
    echo json_encode(['status' => 'error', 'message' => 'No QR token provided']);
    exit;
}

// Find student
$stmt = $pdo->prepare("SELECT * FROM students WHERE qr_token = ?");
$stmt->execute([$qr_token]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    exit;
}

// Toggle logic
$currentStatus = $student['status'];
if ($currentStatus === 'REGISTERED') {
    $newStatus = 'IN';
} elseif ($currentStatus === 'IN') {
    $newStatus = 'OUT';
} else {
    $newStatus = 'IN';
}

// Update status
$stmt = $pdo->prepare("UPDATE students SET status = ? WHERE qr_token = ?");
$stmt->execute([$newStatus, $qr_token]);

$statusEmoji = match ($newStatus) {
    'IN' => '✅',
    'OUT' => '🚪',
    default => '📍'
};

echo json_encode([
    'status' => 'success',
    'message' => "Status updated to $newStatus",
    'name' => $student['name'],
    'new_status' => $newStatus,
    'display' => "$statusEmoji {$student['name']} is now $newStatus"
]);
