<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CutiController;

Route::post('/pengajuan-cuti', [CutiController::class, 'ajukanCuti']);





// Route untuk Registrasi User Baru
//Route::post('/auth/register', [AuthController::class, 'register']);

// Route untuk Login User
//Route::post('/auth/login', [AuthController::class, 'login']);

// Route CRUD yang dilindungi (Middleware harus dipanggil manual jika di web.php)
// Route ini hanya bisa diakses dengan menyertakan Sanctum Token
// Route::middleware('auth:sanctum')->group(function () {
//     // API Resource untuk Jadwal
//     // Endpoint: /api/schedules, /api/schedules/{id}
//     Route::apiResource('schedules', ScheduleController::class);

//     // Contoh route terproteksi lainnya:
//     // Route::get('/user', function (Request $request) {
//     //     return $request->user();
//     // });
// });