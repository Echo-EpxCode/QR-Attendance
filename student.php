<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Invalid token');
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE qr_token = ?");
$stmt->execute([$token]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die('Student not found');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($student['name']) ?> - QR Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen py-12 px-4">
    <div class="max-w-md mx-auto bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 text-center">
        <div class="mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                <?= htmlspecialchars($student['name']) ?>
            </h1>
            <p class="text-gray-600 mb-4">ID: <?= htmlspecialchars($student['student_id']) ?></p>

            <?php
            $statusClass = match ($student['status']) {
                'IN' => 'text-green-600 bg-green-100',
                'OUT' => 'text-orange-600 bg-orange-100',
                default => 'text-gray-600 bg-gray-100'
            };
            ?>
            <div class="<?= $statusClass ?> px-6 py-3 rounded-2xl font-semibold inline-block text-lg">
                📍 <?= $student['status'] ?>
            </div>
        </div>

        <!-- QR Code -->
        <div class="bg-white p-8 rounded-2xl shadow-xl mb-8">
            <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($student['qr_token']) ?>"
                alt="QR Code" class="mx-auto block rounded-xl shadow-lg">
        </div>

        <!-- Download Button -->
        <a id="downloadBtn" download="<?= htmlspecialchars($student['name']) ?>_QR.png"
            class="block w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-4 px-8 rounded-2xl font-semibold text-xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all mb-6">
            💾 Download QR Code
        </a>

        <div class="space-y-4 text-sm text-gray-600">
            <p><strong>Token:</strong> <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?= htmlspecialchars($student['qr_token']) ?></code></p>
            <p>📱 Show this QR to scanner for attendance</p>
        </div>

        <script>
            // Auto-set download link
            document.addEventListener('DOMContentLoaded', function() {
                const qrImage = document.getElementById('qrImage');
                const downloadBtn = document.getElementById('downloadBtn');
                downloadBtn.href = qrImage.src;
            });
        </script>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="students.php" class="text-blue-600 hover:text-blue-800 font-medium block mb-2">← Back to Students</a>
            <a href="scanner.php" target="_blank" class="text-green-600 hover:text-green-800 font-semibold block">🔍 Open Scanner</a>
        </div>
    </div>
</body>

</html>