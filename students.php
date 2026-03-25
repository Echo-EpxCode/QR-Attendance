<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT * FROM students ORDER BY created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - QR Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 mb-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        Students List
                    </h1>
                    <p class="text-gray-600 mt-2">Total: <?= count($students) ?> students</p>
                </div>
                <a href="register.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                    + Register New
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full bg-white rounded-2xl shadow-lg">
                    <thead class="bg-gradient-to-r from-blue-500 to-purple-500 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left rounded-tl-2xl font-semibold">Name</th>
                            <th class="px-6 py-4 text-left font-semibold">Student ID</th>
                            <th class="px-6 py-4 text-left font-semibold">Status</th>
                            <th class="px-6 py-4 text-left font-semibold">QR Token</th>
                            <th class="px-6 py-4 text-left rounded-tr-2xl font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($student['name']) ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($student['student_id']) ?></td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusClass = match ($student['status']) {
                                        'IN' => 'bg-green-100 text-green-800',
                                        'OUT' => 'bg-orange-100 text-orange-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                                        <?= $student['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono truncate max-w-xs"><?= htmlspecialchars($student['qr_token']) ?></td>
                                <td class="px-6 py-4">
                                    <a href="student.php?token=<?= $student['qr_token'] ?>"
                                        class="text-blue-600 hover:text-blue-800 font-medium mr-4">View QR</a>
                                    <a href="scanner.php" target="_blank"
                                        class="text-green-600 hover:text-green-800 font-medium">Scan</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center">
            <a href="scanner.php" target="_blank"
                class="inline-flex items-center bg-gradient-to-r from-green-600 to-emerald-600 text-white px-12 py-4 rounded-2xl font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-200">
                🔍 Start Scanner
            </a>
        </div>
    </div>
</body>

</html>