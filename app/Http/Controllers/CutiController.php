<?php

namespace App\Http\Controllers;

use App\Models\PengajuanCuti;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Digunakan untuk Transaction

class CutiController extends Controller
{
    public function ajukanCuti(Request $request)
    {
        // 1. Validasi Data (Penting!)
        $request->validate([
            'mahasiswa_id' => 'required|integer|exists:Mahasiswa,id', // Pastikan ID Mahasiswa ada
            'semester_cuti' => 'required|string|max:50',
            'lama_cuti_semester' => 'required|integer|min:1',
            'alasan_cuti' => 'required|string|max:500',
        ]);
        
        // Menggunakan Transaction agar kedua operasi (Pengajuan & Log) sukses bersama
        DB::beginTransaction();

        try {
            // 2. CREATE: Simpan data Pengajuan Cuti
            $pengajuan = PengajuanCuti::create([
                'mahasiswa_id' => $request->mahasiswa_id,
                'semester_cuti' => $request->semester_cuti,
                'lama_cuti_semester' => $request->lama_cuti_semester,
                'alasan_cuti' => $request->alasan_cuti,
                'tanggal_pengajuan' => now(), // Waktu pengajuan saat ini
                'status_permohonan' => 'Pending PA', // Status awal
            ]);

            // 3. CREATE: Simpan Log Aktivitas
            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'PENGAJUAN_BARU',
                'dilakukan_oleh' => 'Mahasiswa (ID: ' . $request->mahasiswa_id . ')',
                'timestamp' => now(),
                'catatan' => 'Mahasiswa mengajukan cuti akademik.',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pengajuan cuti berhasil dibuat. Menunggu verifikasi PA.',
                'data' => $pengajuan
            ], 201); // 201 Created

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuat pengajuan cuti.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}