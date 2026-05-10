<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        #reader {
            width: 100%;
            max-width: 500px;
            height: 300px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .status-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            box-shadow: 0 0 20px currentColor;
        }

        .permission-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: 16px;
        }

        .result-screen {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .scan-next-glow {
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.6);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 30px rgba(34, 197, 94, 0.6);
            }

            to {
                box-shadow: 0 0 50px rgba(34, 197, 94, 0.9);
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Scanner Section -->
        <div id="scannerSection" class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 mb-8 text-center">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                QR Scanner
            </h1>
            <p class="text-xl text-gray-700 mb-8">Scan student QR code to mark attendance</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <div class="status-indicator bg-blue-500 shadow-blue-500/50 animate-pulse" id="statusIndicator"></div>
                <div id="statusText" class="text-lg font-semibold text-gray-700">Initializing...</div>
            </div>

            <!-- Camera Scanner -->
            <div id="reader" class="mx-auto mb-8 hidden relative"></div>

            <!-- File Upload Scanner -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-6 rounded-2xl mb-8">
                <label class="block text-lg font-semibold text-gray-800 mb-4 cursor-pointer hover:text-gray-900 transition-colors flex items-center gap-2 justify-center">
                    📁 Or upload QR image
                    <input type="file" id="qrFile" accept="image/*" class="hidden">
                </label>
            </div>

            <!-- Controls -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <button id="requestAccessBtn" class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-8 py-4 rounded-2xl font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all flex items-center gap-2 hidden">
                    🔐 Request Camera Access
                </button>
                <button id="startScan" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-8 py-4 rounded-2xl font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all flex items-center gap-2">
                    🎥 Start Camera
                </button>
                <button id="stopScan" class="bg-gradient-to-r from-red-600 to-rose-600 text-white px-8 py-4 rounded-2xl font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all hidden flex items-center gap-2">
                    ⏹️ Stop Camera
                </button>
            </div>

            <!-- HTTPS Warning -->
            <div id="httpsWarning" class="mt-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-xl hidden">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>⚠️ Camera requires HTTPS or localhost. Use file upload instead.</span>
                </div>
            </div>
        </div>

        <!-- Result Section -->
        <div id="resultSection" class="bg-white/95 backdrop-blur-2xl rounded-3xl shadow-2xl p-12 text-center hidden result-screen">
            <div id="resultContent"></div>
        </div>
    </div>

    <script>
        let html5QrCode = null;
        let isScanning = false;
        let hasPermission = false;

        // DOM Elements
        const statusText = document.getElementById("statusText");
        const statusIndicator = document.getElementById("statusIndicator");
        const startScanBtn = document.getElementById("startScan");
        const stopScanBtn = document.getElementById("stopScan");
        const requestAccessBtn = document.getElementById("requestAccessBtn");
        const qrFileInput = document.getElementById("qrFile");
        const reader = document.getElementById("reader");
        const scannerSection = document.getElementById("scannerSection");
        const resultSection = document.getElementById("resultSection");
        const resultContent = document.getElementById("resultContent");
        const httpsWarning = document.getElementById("httpsWarning");

        // Initialize
        document.addEventListener('DOMContentLoaded', async function() {
            await initScanner();
        });

        async function initScanner() {
            try {
                updateStatus("Initializing scanner...", "blue");

                // Check HTTPS
                if (window.location.protocol !== 'https:' &&
                    window.location.hostname !== 'localhost' &&
                    window.location.hostname !== '127.0.0.1') {
                    httpsWarning.classList.remove('hidden');
                    updateStatus("File upload ready (HTTPS needed for camera)", "yellow");
                    return;
                }

                // ✅ FIXED: Create scanner WITHOUT format constants (they load async)
                html5QrCode = new Html5Qrcode("reader");

                // Wait for library to fully load
                await new Promise(resolve => setTimeout(resolve, 500));

                updateStatus("Scanner ready! Click Start Camera", "green");
                startScanBtn.classList.remove('hidden');
                checkCameraPermission();

            } catch (err) {
                console.error('Init error:', err);
                updateStatus("File upload works • Camera needs HTTPS", "orange");
            }
        }

        async function checkCameraPermission() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true
                });
                stream.getTracks().forEach(track => track.stop());
                hasPermission = true;
            } catch (err) {
                hasPermission = false;
                requestAccessBtn.classList.remove('hidden');
                startScanBtn.classList.add('hidden');
            }
        }

        requestAccessBtn.addEventListener("click", async () => {
            try {
                await navigator.mediaDevices.getUserMedia({
                    video: true
                });
                hasPermission = true;
                requestAccessBtn.classList.add('hidden');
                startScanBtn.classList.remove('hidden');
                updateStatus("Camera ready!", "green");
            } catch (err) {
                updateStatus("Permission denied", "red");
            }
        });

        startScanBtn.addEventListener("click", async () => {
            if (isScanning) return;

            try {
                updateStatus("Starting camera...", "blue");
                reader.classList.remove("hidden");

                const config = {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    },
                    aspectRatio: 1.0,
                    videoConstraints: {
                        facingMode: 'environment'
                    }
                };

                // ✅ FIXED: Correct parameter order
                await html5QrCode.start({
                        facingMode: "environment"
                    },
                    config,
                    onScanSuccess,
                    onScanError
                );

                isScanning = true;
                startScanBtn.classList.add("hidden");
                stopScanBtn.classList.remove("hidden");
                updateStatus("📷 Scanning... Hold QR steady", "green");

            } catch (err) {
                console.error('Camera error:', err);
                updateStatus(`Camera error: ${err.name}`, "red");
            }
        });

        stopScanBtn.addEventListener("click", stopScanner);

        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    reader.classList.add("hidden");
                    startScanBtn.classList.remove("hidden");
                    stopScanBtn.classList.add("hidden");
                    updateStatus("Ready to scan", "blue");
                }).catch(err => console.error("Stop error:", err));
            }
        }

        // File upload
        qrFileInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                updateStatus("Scanning image...", "blue");
                html5QrCode.scanFile(file, true)
                    .then(decodedText => {
                        stopScanner();
                        showResultScreen(decodedText);
                    })
                    .catch(err => {
                        updateStatus("Invalid QR image", "red");
                    });
                e.target.value = '';
            }
        });

        function onScanSuccess(decodedText) {
            // 🛑 STOP CAMERA & SHOW RESULT
            stopScanner();
            showResultScreen(decodedText);
        }

        function onScanError() {
            // Silent
        }

        function showResultScreen(qrToken) {
            scannerSection.classList.add('hidden');
            resultSection.classList.remove('hidden');

            // Show loading
            resultContent.innerHTML = `
                <div class="space-y-6">
                    <div class="w-24 h-24 border-4 border-green-200 border-t-green-500 rounded-full animate-spin mx-auto mb-6"></div>
                    <h2 class="text-3xl font-bold text-gray-800">Processing...</h2>
                    <p class="text-xl text-gray-600">Sending to server</p>
                </div>
            `;

            // Send to server
            fetch('scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        qr_token: qrToken
                    })
                })
                .then(res => res.json())
                .then(data => displayResult(data, qrToken))
                .catch(() => displayResult({
                    status: 'error',
                    message: 'Server error',
                    name: 'Network Issue'
                }, qrToken));
        }

        function displayResult(data, qrToken) {
            if (data.status === 'success') {
                const isIn = data.message.toLowerCase().includes('in');
                const bgColor = isIn ? 'from-emerald-500 to-green-500' : 'from-orange-500 to-yellow-500';
                const icon = isIn ? '✅' : '⏳';
                const title = isIn ? 'CHECKED IN!' : 'CHECKED OUT!';

                resultContent.innerHTML = `
                    <div class="space-y-6">
                        <!-- Status -->
                        <div class="flex flex-col items-center">
                            <div class="w-32 h-32 bg-gradient-to-r ${bgColor} rounded-full flex items-center justify-center mb-6 shadow-2xl">
                                <span class="text-5xl font-black text-white">${icon}</span>
                            </div>
                            <h2 class="text-4xl md:text-5xl font-black text-gray-800 mb-2">${title}</h2>
                            <p class="text-2xl font-semibold text-gray-600">${data.message}</p>
                        </div>

                        <!-- Student Info -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-8 rounded-3xl shadow-xl">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6">Student Details</h3>
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <span class="text-gray-500 font-medium block mb-1">Name:</span>
                                    <p class="text-2xl font-bold text-gray-900">${data.name || 'N/A'}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500 font-medium block mb-1">ID:</span>
                                    <code class="text-lg font-mono bg-gray-100 px-4 py-2 rounded-xl block">${qrToken.substring(0, 20)}${qrToken.length > 20 ? '...' : ''}</code>
                                </div>
                            </div>
                            ${data.timestamp ? `<p class="text-sm text-gray-500 mt-4">${data.timestamp}</p>` : ''}
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <button onclick="scanNext()" class="bg-gradient-to-r from-emerald-600 to-green-600 text-white px-12 py-6 rounded-3xl font-bold text-xl shadow-2xl hover:shadow-3xl transform hover:-translate-y-2 transition-all scan-next-glow flex items-center justify-center gap-3">
                                🔄 Scan Next
                            </button>
                            <button onclick="resetScanner()" class="bg-gradient-to-r from-gray-500 to-gray-600 text-white px-12 py-6 rounded-3xl font-bold text-xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all">
                                ❌ Done
                            </button>
                        </div>
                    </div>
                `;
            } else {
                resultContent.innerHTML = `
                    <div class="space-y-6">
                        <div class="w-32 h-32 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                            <span class="text-5xl">❌</span>
                        </div>
                        <h2 class="text-4xl font-black text-gray-800 mb-4">Failed</h2>
                        <p class="text-2xl text-red-600 font-semibold">${data.message}</p>
                        <button onclick="scanNext()" class="bg-gradient-to-r from-emerald-600 to-green-600 text-white px-12 py-6 rounded-3xl font-bold text-xl shadow-2xl hover:shadow-3xl transform hover:-translate-y-2 transition-all flex items-center mx-auto gap-3">
                            🔄 Try Again
                        </button>
                    </div>
                `;
            }
        }

        function scanNext() {
            resultSection.classList.add('hidden');
            scannerSection.classList.remove('hidden');
            updateStatus("Ready to scan", "green");
        }

        function resetScanner() {
            resultSection.classList.add('hidden');
            scannerSection.classList.remove('hidden');
            updateStatus("Ready to scan", "blue");
        }

        function updateStatus(message, color) {
            statusText.textContent = message;
            const colors = {
                green: 'green-500 shadow-green-500/50',
                red: 'red-500 shadow-red-500/50',
                blue: 'blue-500 shadow-blue-500/50',
                orange: 'orange-500 shadow-orange-500/50',
                yellow: 'yellow-500 shadow-yellow-500/50'
            };
            statusIndicator.className = `status-indicator ${colors[color] || 'bg-blue-500 shadow-blue-500/50'} animate-pulse`;
        }
    </script>
</body>

</html>