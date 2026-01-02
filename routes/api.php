<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\Admin\AdminCutiController;

// Route::middleware('web')->group(function () {
    // 1. POST: Mengajukan permohonan Cuti Akademik baru
    Route::post('/pengajuan-cuti', [CutiController::class, 'ajukanCuti']);

    // 2. GET: Melihat detail status satu permohonan Cuti Akademik
    Route::get('/pengajuan-cuti/{id}', [CutiController::class, 'lihatDetailPengajuan']);

    // 3. GET: Melihat seluruh riwayat pengajuan Cuti Mahasiswa yang bersangkutan
    Route::get('/pengajuan-cuti/riwayat/{mahasiswaId}', [CutiController::class, 'lihatRiwayatMahasiswa']);

    // 4. PUT: Membatalkan pengajuan Cuti Akademik yang statusnya masih Pending
    Route::put('/pengajuan-cuti/{id}/batal', [CutiController::class, 'batalkanPengajuan']);

    // 5. POST: Mengajukan permohonan untuk Aktif Kembali setelah masa cuti berakhir
    Route::post('/aktif-kembali', [CutiController::class, 'ajukanAktifKembali']);

    // No. 14, 15 (Admin)
    Route::get('/report/statistik-cuti', [CutiController::class, 'statistikCuti']);
    Route::put('/admin/perpanjang-cuti/{id}', [CutiController::class, 'perpanjangCuti']);
// });

// Route::prefix('admin')->group(function () {
    Route::get('/mahasiswa-cuti', [AdminCutiController::class, 'daftarMahasiswaCuti']);
    Route::put('/status-cuti/{id}', [AdminCutiController::class, 'terbitkanSk']);


Route::prefix('pa')->group(function () {
        Route::get('/pengajuan/pending', [CutiController::class, 'listPendingPA']);
        Route::put('/pengajuan/{id}/setujui', [CutiController::class, 'setujuiPA']);
        Route::put('/pengajuan/{id}/tolak', [CutiController::class, 'tolakPA']);
    });
