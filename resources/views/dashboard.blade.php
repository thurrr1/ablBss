<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Dashboard Pengelolaan Cuti Akademik (BSS)') }}
        </h2>
    </x-slot>

    {{-- ****************************************************** --}}
    {{-- PASTIKAN LAYOUT ANDA MEMILIKI META TAG CSRF INI DULU --}}
    {{-- Tambahkan di layout utama atau di sini jika belum ada --}}
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    {{-- ****************************************************** --}}

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- ========================================================== -->
            <!-- 1. MODUL STATUS CUTI TERKINI (GET /riwayat/{id}) -->
            <!-- ========================================================== -->
            <div id="status-card" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-indigo-500">
                <h3 class="text-xl font-bold mb-4 text-indigo-600">Status Pengajuan Terakhir</h3>
                
                <div id="status-loading" class="text-gray-500">Memuat status...</div>
                
                <div id="status-content" class="hidden">
                    <p class="text-sm text-gray-500">Pengajuan Cuti:</p>
                    <p id="cuti-semester" class="text-2xl font-extrabold text-gray-700">-</p>
                    
                    <div class="mt-4 flex items-center space-x-3">
                        <span id="status-badge" class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-200 text-gray-800">Status: Tidak Ada Pengajuan</span>
                        
                        <!-- Aksi Cepat: Tombol Batalkan atau Aktif Kembali -->
                        <button id="btn-batalkan" 
                                class="hidden px-4 py-2 bg-red-600 text-white font-medium text-sm rounded-lg shadow hover:bg-red-700 transition duration-150">
                            Batalkan Pengajuan
                        </button>

                        <button id="btn-aktif-kembali" 
                                class="hidden px-4 py-2 bg-green-600 text-white font-medium text-sm rounded-lg shadow hover:bg-green-700 transition duration-150">
                            Ajukan Aktif Kembali
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- 2. MODUL PENGAJUAN BARU (POST /pengajuan-cuti) -->
            <!-- ========================================================== -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Ajukan Cuti Akademik Baru</h3>
                <form id="form-pengajuan-cuti" class="space-y-4">
                    <!-- ID Mahasiswa diambil dari Auth::id() di script bawah -->
                    
                    <div>
                        <label for="semester_cuti" class="block text-sm font-medium text-gray-700">Semester Cuti</label>
                        <input type="text" id="semester_cuti" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Contoh: Genap 2025/2026" required>
                    </div>

                    <div>
                        <label for="lama_cuti_semester" class="block text-sm font-medium text-gray-700">Lama Cuti (Semester)</label>
                        <select id="lama_cuti_semester" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            <option value="1">1 Semester</option>
                            <option value="2">2 Semester</option>
                        </select>
                    </div>

                    <div>
                        <label for="alasan_cuti" class="block text-sm font-medium text-gray-700">Alasan Cuti</label>
                        <textarea id="alasan_cuti" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Jelaskan alasan pengajuan cuti Anda secara detail." required></textarea>
                    </div>
                    
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150">
                        Ajukan Cuti
                    </button>
                    <p id="form-message" class="mt-2 text-sm hidden"></p>
                </form>
            </div>

            <!-- ========================================================== -->
            <!-- 3. MODUL RIWAYAT PENGAJUAN (GET /riwayat/{id}) -->
            <!-- ========================================================== -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Riwayat Pengajuan Cuti</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester Cuti</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lama Cuti</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="riwayat-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Data akan dimuat di sini oleh JavaScript -->
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500" id="riwayat-empty">Memuat data riwayat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal untuk Detail Pengajuan -->
    <div id="detail-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-2xl rounded-lg bg-white">
            <h3 class="text-xl font-bold mb-4 border-b pb-2">Detail Pengajuan Cuti #<span id="modal-id"></span></h3>
            
            <div class="space-y-3">
                <p><span class="text-sm font-medium text-gray-600">Alasan Pengajuan:</span>
                    <p id="modal-alasan" class="text-gray-800 bg-gray-50 p-3 rounded-md"></p>
                </p>
                <p><span class="text-sm font-medium text-gray-600">Status Terkini:</span>
                    <span id="modal-status" class="font-bold"></span>
                </p>
                <p class="text-sm font-medium text-gray-600">Log Aktivitas:</p>
                <div id="modal-log" class="text-xs text-gray-600 italic border p-3 rounded-md max-h-40 overflow-y-auto"></div>
            </div>

            <div class="mt-6 text-right">
                <button onclick="document.getElementById('detail-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-150">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        // ******************************************************
        // PERBAIKAN: Menggunakan ID Mahasiswa hardcode = 1
        // ******************************************************
        const MAHASSWA_ID = 1; 
        const API_BASE_URL = '/api'; 

        // Mengambil CSRF Token dari meta tag (asumsi sudah ada di layout)
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

        document.addEventListener('DOMContentLoaded', function() {
            // Kita tidak perlu mengecek Auth::id() di sini karena sudah hardcode.
            // Kita hanya perlu memastikan ID Mahasiswa 1 ada di DB.
            loadRiwayat(MAHASSWA_ID);
            document.getElementById('form-pengajuan-cuti').addEventListener('submit', handlePengajuanSubmit);
        });

        // ==========================================================
        // UTILITY FUNCTIONS
        // ==========================================================
        
        function getStatusClass(status) {
            let base = 'px-3 py-1 text-sm font-semibold rounded-full ';
            switch (status) {
                case 'Diterbitkan SK':
                    return base + 'bg-green-200 text-green-800';
                case 'Pending PA':
                    return base + 'bg-yellow-200 text-yellow-800';
                case 'Dibatalkan Mahasiswa':
                case 'Ditolak PA':
                    return base + 'bg-red-200 text-red-800';
                case 'Pending Aktif Kembali':
                    return base + 'bg-indigo-200 text-indigo-800';
                default:
                    return base + 'bg-gray-200 text-gray-800';
            }
        }

        // Fungsi simulasi untuk menentukan apakah tombol Aktif Kembali harus muncul
        function isCutiSelesai(data) {
            // Untuk testing, kita cek statusnya saja
            return data.status_permohonan === 'Diterbitkan SK';
        }

        // ==========================================================
        // FUNGSI API CALLS
        // ==========================================================

        async function loadRiwayat(mahasiswaId) {
            const tableBody = document.getElementById('riwayat-table-body');
            const statusContent = document.getElementById('status-content');
            const statusLoading = document.getElementById('status-loading');
            
            tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">Memuat data riwayat...</td></tr>';
            statusLoading.classList.remove('hidden');
            statusContent.classList.add('hidden');

            try {
                // Endpoint GET /api/pengajuan-cuti/riwayat/{mahasiswaId}
                const response = await fetch(`${API_BASE_URL}/pengajuan-cuti/riwayat/${mahasiswaId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest', 
                    },
                    credentials: 'include' // Mengirim session cookies untuk middleware auth
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    console.error('API Error Response:', response.status, errorData);
                    
                    tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-red-600">Gagal memuat riwayat. Akses Ditolak atau Data Belum Ada.</td></tr>';
                    document.getElementById('status-badge').textContent = 'Error koneksi server.';
                    
                    return;
                }

                const result = await response.json();

                if (!result.data || result.data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">Belum ada riwayat pengajuan cuti.</td></tr>';
                    document.getElementById('cuti-semester').textContent = 'Tidak Ada';
                    document.getElementById('status-badge').textContent = 'Status: Aktif / Tidak Ada Pengajuan';
                    document.getElementById('status-badge').className = getStatusClass('Aktif');
                    return;
                }

                // Ambil data terbaru untuk Modul Status Terkini
                const latest = result.data[0];
                renderStatusCard(latest);

                // Render Tabel Riwayat
                tableBody.innerHTML = '';
                result.data.forEach((item, index) => {
                    tableBody.innerHTML += `
                        <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                            <td class="px-4 py-4 whitespace-nowrap">${index + 1}</td>
                            <td class="px-4 py-4 whitespace-nowrap">${item.semester_cuti}</td>
                            <td class="px-4 py-4 whitespace-nowrap">${item.lama_cuti_semester} Semester</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="${getStatusClass(item.status_permohonan)}">${item.status_permohonan}</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <button onclick="showDetail(${item.id})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                    `;
                });

            } catch (error) {
                console.error('Error fetching riwayat (Catch Block):', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-red-500">Gagal memuat data. Periksa konsol untuk detail error koneksi.</td></tr>';
                document.getElementById('status-badge').textContent = 'Error koneksi server.';
            } finally {
                statusLoading.classList.add('hidden');
                statusContent.classList.remove('hidden');
            }
        }

        function renderStatusCard(data) {
            document.getElementById('cuti-semester').textContent = `${data.lama_cuti_semester} Semester (Diajukan untuk ${data.semester_cuti})`;
            document.getElementById('status-badge').textContent = `Status: ${data.status_permohonan}`;
            document.getElementById('status-badge').className = getStatusClass(data.status_permohonan);

            // Tampilkan atau sembunyikan tombol aksi cepat
            const btnBatalkan = document.getElementById('btn-batalkan');
            const btnAktifKembali = document.getElementById('btn-aktif-kembali');

            btnBatalkan.classList.add('hidden');
            btnAktifKembali.classList.add('hidden');

            if (data.status_permohonan === 'Pending PA') {
                btnBatalkan.classList.remove('hidden');
                btnBatalkan.setAttribute('onclick', `batalkanCuti(${data.id})`);
            } else if (isCutiSelesai(data)) { // Gunakan isCutiSelesai untuk menentukan apakah tombol Aktif Kembali harus muncul
                btnAktifKembali.classList.remove('hidden');
                btnAktifKembali.setAttribute('onclick', `ajukanAktifKembali(${data.id})`);
            }
        }

        async function handlePengajuanSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const messageElement = document.getElementById('form-message');
            messageElement.classList.add('hidden');
            
            const payload = {
                // Menggunakan MAHASSWA_ID hardcode 1
                mahasiswa_id: MAHASSWA_ID, 
                semester_cuti: document.getElementById('semester_cuti').value,
                lama_cuti_semester: parseInt(document.getElementById('lama_cuti_semester').value),
                alasan_cuti: document.getElementById('alasan_cuti').value
            };

            try {
                // Endpoint POST /api/pengajuan-cuti
                const response = await fetch(`${API_BASE_URL}/pengajuan-cuti`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                    credentials: 'include' 
                });

                const result = await response.json();
                
                messageElement.classList.remove('hidden');
                
                if (response.ok) {
                    messageElement.className = 'mt-2 text-sm text-green-600';
                    messageElement.textContent = result.message;
                    form.reset();
                    loadRiwayat(MAHASSWA_ID); // Reload data
                } else {
                    messageElement.className = 'mt-2 text-sm text-red-600';
                    // Tampilkan pesan error validasi jika ada
                    if (response.status === 422 && result.errors) {
                        messageElement.textContent = "Validasi Gagal: " + Object.values(result.errors).flat().join(', ');
                    } else {
                         messageElement.textContent = result.message || "Pengajuan gagal.";
                    }
                }
            } catch (error) {
                messageElement.className = 'mt-2 text-sm text-red-600';
                messageElement.textContent = 'Gagal menghubungi server.';
                console.error('Submit Error:', error);
            }
        }

        async function showDetail(id) {
            try {
                // Endpoint GET /api/pengajuan-cuti/{id}
                const response = await fetch(`${API_BASE_URL}/pengajuan-cuti/${id}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'include'
                });
                const data = await response.json();

                if (response.ok) {
                    const pengajuan = data.data;
                    document.getElementById('modal-id').textContent = pengajuan.id;
                    document.getElementById('modal-alasan').textContent = pengajuan.alasan_cuti;
                    document.getElementById('modal-status').textContent = pengajuan.status_permohonan;
                    document.getElementById('modal-status').className = getStatusClass(pengajuan.status_permohonan);
                    
                    const logContent = pengajuan.log_aktivitas.map(log => 
                        `<div class="py-1 border-b last:border-b-0">${new Date(log.timestamp).toLocaleDateString()} (${log.tipe_aktivitas} oleh ${log.dilakukan_oleh}): ${log.catatan}</div>`
                    ).join('');
                    
                    document.getElementById('modal-log').innerHTML = logContent || 'Tidak ada log aktivitas.';
                    
                    document.getElementById('detail-modal').classList.remove('hidden');
                } else {
                    alert('Gagal memuat detail: ' + (data.message || 'Error server.'));
                }
            } catch (error) {
                alert('Terjadi kesalahan saat mengambil detail.');
                console.error(error);
            }
        }
        
        async function batalkanCuti(id) {
             if (!confirm(`Yakin ingin membatalkan pengajuan cuti ID ${id}? Aksi ini tidak dapat dibatalkan.`)) return;

             try {
                // Endpoint PUT /api/pengajuan-cuti/{id}/batal
                const response = await fetch(`${API_BASE_URL}/pengajuan-cuti/${id}/batal`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ mahasiswa_id: MAHASSWA_ID }), // Kirim ID mahasiswa yang membatalkan
                    credentials: 'include'
                });
                const result = await response.json();

                if (response.ok) {
                    alert('Pembatalan berhasil: ' + result.message);
                    loadRiwayat(MAHASSWA_ID);
                } else {
                    alert('Pembatalan gagal: ' + (result.message || 'Status tidak valid.'));
                }
            } catch (error) {
                alert('Error koneksi saat membatalkan.');
            }
        }

        async function ajukanAktifKembali(pengajuanId) {
             const semesterAktif = prompt("Masukkan semester Anda akan aktif kembali (Contoh: Gasal 2026/2027):");
             if (!semesterAktif) return;

             try {
                // Endpoint POST /api/aktif-kembali
                const response = await fetch(`${API_BASE_URL}/aktif-kembali`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ 
                        pengajuan_id: pengajuanId,
                        semester_aktif: semesterAktif 
                    }),
                    credentials: 'include'
                });
                const result = await response.json();

                if (response.ok) {
                    alert('Pengajuan Aktif Kembali berhasil: ' + result.message);
                    loadRiwayat(MAHASSWA_ID);
                } else {
                    alert('Pengajuan Aktif Kembali gagal: ' + (result.message || 'Error.'));
                }
            } catch (error) {
                alert('Error koneksi saat mengajukan aktif kembali.');
            }
        }
    </script>
</x-app-layout>