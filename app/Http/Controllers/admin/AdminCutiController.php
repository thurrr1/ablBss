<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanCuti;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCutiController extends Controller
{
    /**
     * No. 9 (READ)
     * Mendapatkan daftar seluruh mahasiswa yang sedang berstatus Cuti Akademik
     */
    public function daftarMahasiswaCuti()
    {
        $data = PengajuanCuti::with(['mahasiswa:id,nim,nama,prodi'])
            ->whereIn('status_permohonan', ['Diterbitkan SK', 'Pending Aktif Kembali'])
            ->orderBy('tanggal_pengajuan', 'desc')
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada mahasiswa yang sedang berstatus Cuti Akademik.'
            ], 404);
        }

        return response()->json([
            'message' => 'Daftar mahasiswa cuti berhasil diambil.',
            'total_data' => $data->count(),
            'data' => $data
        ], 200);
    }

    /**
     * No. 10 (UPDATE)
     * Mengubah status cuti dari Disetujui menjadi Diterbitkan SK
     */
    public function terbitkanSk(Request $request, $id)
    {
        $request->validate([
            'admin_name' => 'required|string|max:100',
        ]);

        $pengajuan = PengajuanCuti::find($id);
        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan.'], 404);
        }

        if ($pengajuan->status_permohonan !== 'Disetujui PA') {
            return response()->json([
                'message' => 'Status tidak dapat diubah. Status saat ini: ' . $pengajuan->status_permohonan
            ], 400);
        }

        DB::beginTransaction();
        try {
            $pengajuan->update([
                'status_permohonan' => 'Diterbitkan SK',
            ]);

            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'TERBIT_SK',
                'dilakukan_oleh' => $request->admin_name,
                'timestamp' => now(),
                'catatan' => 'Status cuti diubah menjadi Diterbitkan SK.'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Status cuti berhasil diubah menjadi Diterbitkan SK.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menerbitkan SK cuti.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
