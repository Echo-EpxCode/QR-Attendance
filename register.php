<?php
require_once 'config.php';

$successStudent = null;
$message = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $student_id = trim($_POST['student_id']);

    if (empty($name) || empty($student_id)) {
        $message = 'Please fill all fields';
    } else {
        // Check if student_id exists
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);

        if ($stmt->fetch()) {
            $message = 'Student ID already exists';
        } else {
            // Generate unique QR token
            do {
                $qr_token = $student_id;
                $stmt = $pdo->prepare("SELECT id FROM students WHERE qr_token = ?");
                $stmt->execute([$qr_token]);
            } while ($stmt->fetch());

            // Insert student
            $stmt = $pdo->prepare("INSERT INTO students (name, student_id, qr_token, status) VALUES (?, ?, ?, 'REGISTERED')");
            $stmt->execute([$name, $student_id, $qr_token]);

            // Get the newly created student
            $stmt = $pdo->prepare("SELECT * FROM students WHERE qr_token = ?");
            $stmt->execute([$qr_token]);
            $successStudent = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student - QR Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen py-12 px-4">
    <div class="max-w-2xl mx-auto">

        <!-- Main Card -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                    QR Attendance
                </h1>
                <p class="text-gray-600">Register new student</p>
            </div>

            <!-- Error Message -->
            <?php if ($message && !$successStudent): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-8 text-center font-semibold">
                    ⚠️ <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- SUCCESS: QR Display (No Alert) -->
            <?php if ($successStudent): ?>
                <div class="text-center mb-12 p-8 bg-gradient-to-br from-green-50 to-emerald-50 rounded-3xl border-4 border-green-200">
                    <div class="text-3xl mb-6">🎉</div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Student Registered Successfully!</h2>

                    <div class="bg-white p-8 rounded-2xl shadow-2xl mx-auto mb-6 max-w-xs">
                        <img id="successQrImage"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($successStudent['qr_token']) ?>"
                            alt="QR Code"
                            class="mx-auto block rounded-xl shadow-lg">
                    </div>

                    <div class="space-y-3 mb-8 p-6 bg-white/50 rounded-2xl">
                        <div class="font-bold text-xl text-gray-800"><?= htmlspecialchars($successStudent['name']) ?></div>
                        <div class="text-gray-600">ID: <?= htmlspecialchars($successStudent['student_id']) ?></div>
                        <div class="text-sm text-gray-500 bg-gray-100 px-4 py-2 rounded-xl inline-block font-mono">
                            <?= htmlspecialchars($successStudent['qr_token']) ?>
                        </div>
                        <div class="text-green-700 font-semibold bg-green-100 px-4 py-2 rounded-xl inline-block text-lg">
                            📍 Status: <?= $successStudent['status'] ?> → Ready for first scan
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 max-w-2xl mx-auto">
                        <a href="student.php?token=<?= $successStudent['qr_token'] ?>"
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 px-6 rounded-2xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all text-center">
                            👁️ Full QR Page
                        </a>
                        <a id="downloadSuccessQr"
                            download="<?= htmlspecialchars($successStudent['name']) ?>_QR.png"
                            class="bg-gradient-to-r from-green-600 to-emerald-600 text-white py-4 px-6 rounded-2xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all text-center">
                            💾 Download QR
                        </a>
                        <a href="scanner.php" target="_blank"
                            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white py-4 px-6 rounded-2xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all text-center">
                            🔍 Test Scanner
                        </a>
                    </div>

                    <div class="text-sm text-gray-600 space-y-2 p-4 bg-white/30 rounded-xl">
                        <p><strong>Next Steps:</strong></p>
                        <p>📱 Show QR to scanner → <strong>REGISTERED → IN</strong></p>
                        <p>🔄 Next scan → <strong>IN → OUT</strong> (toggle forever)</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form (Always Visible) -->
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                        placeholder="Enter student name">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                    <input type="text" name="student_id" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                        placeholder="Enter student ID">
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 px-6 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <span class="mr-2">👤</span>Register Student
                </button>
            </form>

            <!-- Navigation -->
            <div class="mt-12 pt-8 border-t border-gray-200 grid grid-cols-2 gap-4 text-center">
                <a href="index.php" class="block p-4 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold rounded-xl transition-all">
                    🏠 Dashboard
                </a>
                <a href="students.php" class="block p-4 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-semibold rounded-xl transition-all">
                    📋 All Students
                </a>
                <a href="scanner.php" target="_blank" class="block p-6 mt-4 bg-green-100 hover:bg-green-200 text-green-800 font-bold rounded-2xl transition-all col-span-2">
                    🔍 Open Scanner
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-set download link for success QR
        document.addEventListener('DOMContentLoaded', function() {
            const qrImage = document.getElementById('successQrImage');
            const downloadBtn = document.getElementById('downloadSuccessQr');
            if (qrImage && downloadBtn) {
                downloadBtn.href = qrImage.src;
            }
        });
    </script>
</body>

</html>