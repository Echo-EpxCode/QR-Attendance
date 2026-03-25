<?php
require_once 'config.php';

// Stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'registered' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'REGISTERED'")->fetchColumn(),
    'in' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'IN'")->fetchColumn(),
    'out' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'OUT'")->fetchColumn()
];

// Recent activity
$stmt = $pdo->query("
    SELECT s.name, s.student_id, s.status, s.updated_at 
    FROM students s 
    ORDER BY s.updated_at DESC 
    LIMIT 10
");
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 mb-8">
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
                    QR Attendance System
                </h1>
                <p class="text-xl text-gray-700">Toggle attendance with QR scans (IN ↔ OUT)</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-8 rounded-2xl shadow-xl">
                    <div class="text-3xl font-bold"><?= $stats['total'] ?></div>
                    <div class="text-blue-100">Total Students</div>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-8 rounded-2xl shadow-xl">
                    <div class="text-3xl font-bold"><?= $stats['in'] ?></div>
                    <div class="text-green-100">Currently IN</div>
                </div>
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white p-8 rounded-2xl shadow-xl">
                    <div class="text-3xl font-bold"><?= $stats['out'] ?></div>
                    <div class="text-orange-100">Currently OUT</div>
                </div>
                <div class="bg-gradient-to-br from-gray-500 to-gray-600 text-white p-8 rounded-2xl shadow-xl">
                    <div class="text-3xl font-bold"><?= $stats['registered'] ?></div>
                    <div class="text-gray-100">Not Scanned</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <a href="register.php" class="group bg-gradient-to-r from-blue-600 to-purple-600 text-white p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all text-center">
                    <div class="text-4xl mb-4">👤</div>
                    <div class="text-xl font-bold mb-2 group-hover:text-blue-100">Register Student</div>
                    <div class="text-blue-100">Add new students</div>
                </a>
                <a href="students.php" class="group bg-gradient-to-r from-indigo-600 to-indigo-700 text-white p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all text-center">
                    <div class="text-4xl mb-4">📋</div>
                    <div class="text-xl font-bold mb-2 group-hover:text-indigo-100">View Students</div>
                    <div class="text-indigo-100">See all records</div>
                </a>
                <a href="scanner.php" target="_blank" class="group bg-gradient-to-r from-green-600 to-emerald-600 text-white p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all text-center">
                    <div class="text-4xl mb-4">🔍</div>
                    <div class="text-xl font-bold mb-2 group-hover:text-green-100">Start Scanner</div>
                    <div class="text-green-100">Camera + File upload</div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Recent Activity</h2>
            <div class="space-y-4">
                <?php foreach ($recent as $activity): ?>
                    <div class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl flex items-center justify-center text-white font-bold text-lg mr-4 flex-shrink-0">
                            <?= substr($activity['name'], 0, 1) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($activity['name']) ?></div>
                            <div class="text-sm text-gray-500 truncate"><?= htmlspecialchars($activity['student_id']) ?></div>
                        </div>
                        <div class="ml-4">
                            <?php
                            $statusClass = match ($activity['status']) {
                                'IN' => 'text-green-600 bg-green-100',
                                'OUT' => 'text-orange-600 bg-orange-100',
                                default => 'text-gray-600 bg-gray-100'
                            };
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                                <?= $activity['status'] ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>