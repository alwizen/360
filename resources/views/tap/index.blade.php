<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PreTrip Tap System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">

            {{-- Header --}}
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">PreTrip 360 System</h1>
                <p class="text-gray-600">Scan atau masukkan RFID Code untuk mencatat tap</p>
            </div>

            {{-- Form Tap --}}
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form id="tapForm">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">
                            RFID Code
                        </label>
                        <input type="text" id="rfid_code" name="rfid_code"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                            placeholder="Scan atau ketik RFID code..." autofocus>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                        TAP SEKARANG
                    </button>
                </form>
            </div>

            {{-- Message --}}
            <div id="messageBox" class="hidden mb-6 p-4 rounded-lg">
                <p id="messageText" class="font-semibold text-lg"></p>
            </div>

            {{-- Last Tap Info --}}
            <div id="lastTapBox"
                class="hidden bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <h2 class="text-2xl font-bold mb-4">✅ Tap Terakhir</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-green-100 text-sm">Truck</p>
                        <p id="lastTapTruck" class="text-2xl font-bold"></p>
                    </div>
                    <div>
                        <p class="text-green-100 text-sm">Waktu</p>
                        <p id="lastTapTime" class="text-2xl font-bold"></p>
                    </div>
                    <div>
                        <p class="text-green-100 text-sm">Lokasi</p>
                        <p id="lastTapLocation" class="text-xl font-semibold"></p>
                    </div>
                    <div>
                        <p class="text-green-100 text-sm">Urutan Tap</p>
                        <p id="lastTapSequence" class="text-2xl font-bold"></p>
                    </div>
                </div>
            </div>

            {{-- Current PreTrip Status --}}
            <div id="pretripBox" class="hidden bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">📋 PreTrip Aktif</h2>
                    <button id="resetBtn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                        Reset Session
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-gray-600 text-sm">Truck</p>
                        <p id="pretripTruck" class="font-bold text-lg"></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Tanggal</p>
                        <p id="pretripDate" class="font-bold text-lg"></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Status</p>
                        <span id="pretripStatus"
                            class="inline-block px-3 py-1 rounded-full text-sm font-semibold"></span>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Progress</p>
                        <p id="pretripProgress" class="font-bold text-lg"></p>
                    </div>
                </div>

                <h3 class="font-bold text-gray-700 mb-3">Riwayat Tap:</h3>
                <div id="tapsList" class="space-y-2"></div>
            </div>

            {{-- No Active Pretrip --}}
            <div id="noActiveBox" class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-blue-700">
                    ℹ️ Belum ada PreTrip aktif. Scan RFID untuk memulai PreTrip baru.
                </p>
            </div>

        </div>
    </div>

    <script>
        // Setup CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Elements
        const tapForm = document.getElementById('tapForm');
        const rfidInput = document.getElementById('rfid_code');
        const messageBox = document.getElementById('messageBox');
        const messageText = document.getElementById('messageText');
        const lastTapBox = document.getElementById('lastTapBox');
        const pretripBox = document.getElementById('pretripBox');
        const noActiveBox = document.getElementById('noActiveBox');
        const resetBtn = document.getElementById('resetBtn');

        // Load active pretrip on page load
        loadActivePretrip();

        // Form submit
        tapForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const rfidCode = rfidInput.value.trim();
            if (!rfidCode) return;

            try {
                const response = await fetch('/tap/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        rfid_code: rfidCode
                    })
                });

                const data = await response.json();

                // Show message
                showMessage(data.message, data.type);

                if (data.success) {
                    // Show last tap
                    showLastTap(data.last_tap);

                    // Update pretrip
                    updatePretrip(data.pretrip);
                }

                // Reset input
                rfidInput.value = '';
                rfidInput.focus();

            } catch (error) {
                showMessage('❌ Terjadi kesalahan!', 'error');
                console.error(error);
            }
        });

        // Reset button
        resetBtn.addEventListener('click', () => {
            lastTapBox.classList.add('hidden');
            messageBox.classList.add('hidden');
            rfidInput.value = '';
            rfidInput.focus();
            loadActivePretrip();
        });

        // Functions
        function showMessage(message, type) {
            messageText.textContent = message;
            messageBox.className = 'mb-6 p-4 rounded-lg ';

            if (type === 'success') {
                messageBox.className += 'bg-green-100 text-green-800';
            } else if (type === 'error') {
                messageBox.className += 'bg-red-100 text-red-800';
            } else {
                messageBox.className += 'bg-yellow-100 text-yellow-800';
            }

            messageBox.classList.remove('hidden');
        }

        function showLastTap(tap) {
            document.getElementById('lastTapTruck').textContent = tap.truck;
            document.getElementById('lastTapTime').textContent = tap.time;
            document.getElementById('lastTapLocation').textContent = `Point ${tap.point_number} - ${tap.location}`;
            document.getElementById('lastTapSequence').textContent = `#${tap.sequence}`;
            lastTapBox.classList.remove('hidden');
        }

        function updatePretrip(pretrip) {
            document.getElementById('pretripTruck').textContent = pretrip.truck_id;
            document.getElementById('pretripDate').textContent = pretrip.trip_date;
            document.getElementById('pretripProgress').textContent = pretrip.progress + '%';

            const statusEl = document.getElementById('pretripStatus');
            statusEl.textContent = pretrip.status === 'completed' ? 'Completed' : 'In Progress';
            statusEl.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold ';
            statusEl.className += pretrip.status === 'completed' ?
                'bg-green-100 text-green-800' :
                'bg-yellow-100 text-yellow-800';

            // Update taps list
            const tapsList = document.getElementById('tapsList');
            tapsList.innerHTML = '';

            pretrip.taps.forEach(tap => {
                tapsList.innerHTML += `
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="bg-blue-500 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">
                                ${tap.sequence}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">
                                    Point ${tap.point_number} - ${tap.location}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600 text-sm">${tap.time}</p>
                        </div>
                    </div>
                `;
            });

            pretripBox.classList.remove('hidden');
            noActiveBox.classList.add('hidden');
        }

        async function loadActivePretrip() {
            try {
                const response = await fetch('/tap/active');
                const data = await response.json();

                if (data.active) {
                    updatePretrip(data.pretrip);
                } else {
                    pretripBox.classList.add('hidden');
                    noActiveBox.classList.remove('hidden');
                }
            } catch (error) {
                console.error(error);
            }
        }
    </script>
</body>

</html>
