<?php
require_once 'config.php';

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
                $qr_token = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("SELECT id FROM students WHERE qr_token = ?");
                $stmt->execute([$qr_token]);
            } while ($stmt->fetch());

            // Insert student
            $stmt = $pdo->prepare("INSERT INTO students (name, student_id, qr_token, status) VALUES (?, ?, ?, 'REGISTERED')");
            $stmt->execute([$name, $student_id, $qr_token]);

            $message = "Student registered successfully! QR Token: $qr_token";
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
    <div class="max-w-md mx-auto bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                QR Attendance
            </h1>
            <p class="text-gray-600">Register new student</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter student name">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                <input type="text" name="student_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter student ID">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                Register Student
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <a href="students.php" class="block text-center text-blue-600 hover:text-blue-800 font-medium transition-colors">
                View All Students →
            </a>
        </div>
    </div>
</body>

</html>