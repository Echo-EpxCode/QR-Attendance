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
    </style>
</head>

<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 mb-8 text-center">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                QR Scanner
            </h1>
            <p class="text-xl text-gray-700 mb-8">Scan student QR code to mark attendance</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <div class="status-indicator bg-green-500 shadow-green-500/50 animate-pulse" id="statusIndicator"></div>
                <div id="statusText" class="text-lg font-semibold text-gray-700">Ready to scan</div>
            </div>

            <!-- Camera Scanner -->
            <div id="reader" class="mx-auto mb-8 hidden"></div>

            <!-- File Upload Scanner -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-6 rounded-2xl mb-8">
                <label class="block text-lg font-semibold text-gray-800 mb-4 cursor-pointer hover:text-gray-900 transition-colors">
                    📁 Or upload QR image
                    <input type="file" id="qrFile" accept="image/*" class="hidden">
                </label>
            </div>

            <!-- Controls -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <button id="startScan" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-8 py-4 rounded-2xl font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all flex items-center gap-2">
                    🎥 Start Camera
                </button>
                <button id="stopScan" class="bg-gradient-to-r from-red-600 to-rose-600 text-white px-8 py-4 rounded-2xl font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all hidden flex items-center gap-2">
                    ⏹️ Stop Camera
                </button>
            </div>
        </div>

        <div class="text-center">
            <a href="students.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-semibold text-lg">
                👥 View Students
            </a>
        </div>
    </div>

    <script>
        const html5QrCode = new Html5Qrcode("reader");
        const statusText = document.getElementById("statusText");
        const statusIndicator = document.getElementById("statusIndicator");
        const startScanBtn = document.getElementById("startScan");
        const stopScanBtn = document.getElementById("stopScan");
        const qrFileInput = document.getElementById("qrFile");
        const reader = document.getElementById("reader");

        let isScanning = false;

        // Camera scan
        startScanBtn.addEventListener("click", async () => {
            if (isScanning) return;

            try {
                const cameras = await html5QrCode.getCameras();
                if (cameras.length === 0) {
                    updateStatus("No cameras found", "orange");
                    return;
                }

                reader.classList.remove("hidden");
                await html5QrCode.start({
                        facingMode: "environment"
                    }, {
                        fps: 10,
                        qrbox: {
                            width: 250,
                            height: 250
                        }
                    },
                    onScanSuccess,
                    onScanError
                );

                isScanning = true;
                startScanBtn.classList.add("hidden");
                stopScanBtn.classList.remove("hidden");
                updateStatus("Scanning... Point QR at camera", "green");

            } catch (err) {
                updateStatus("Camera access denied", "red");
                console.error(err);
            }
        });

        stopScanBtn.addEventListener("click", () => {
            html5QrCode.stop().then(() => {
                isScanning = false;
                reader.classList.add("hidden");
                startScanBtn.classList.remove("hidden");
                stopScanBtn.classList.add("hidden");
                updateStatus("Ready to scan", "blue");
            }).catch(err => {
                console.error("Stop failed:", err);
            });
        });

        // File upload scan
        qrFileInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                updateStatus("Scanning uploaded image...", "blue");
                html5QrCode.scanFile(file, true)
                    .then(decodedText => {
                        sendToServer(decodedText);
                        e.target.value = ''; // Reset input
                    })
                    .catch(err => {
                        console.error("File scan error:", err);
                        updateStatus("Invalid QR code in image", "red");
                    });
            }
        });

        function onScanSuccess(decodedText) {
            sendToServer(decodedText);
        }

        function onScanError() {
            // Silent error for continuous scanning
        }

        function sendToServer(token) {
            fetch('scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        qr_token: token
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateStatus(`${data.name}: ${data.message}`, "green");
                        statusIndicator.classList.add('animate-pulse');
                        setTimeout(() => {
                            statusIndicator.classList.remove('animate-pulse');
                        }, 2000);
                    } else {
                        updateStatus(data.message, "red");
                    }
                })
                .catch(err => {
                    console.error("Server error:", err);
                    updateStatus("Server error. Try again.", "red");
                });
        }

        function updateStatus(message, color) {
            statusText.textContent = message;
            statusIndicator.className = `status-indicator bg-${color}-500 shadow-${color}-500/50`;

            if (color === 'green') {
                statusIndicator.classList.add('animate-bounce');
                setTimeout(() => {
                    statusIndicator.classList.remove('animate-bounce');
                }, 1000);
            }
        }
    </script>
</body>

</html>