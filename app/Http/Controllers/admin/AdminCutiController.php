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

    /**
     * No. 11 (CREATE)
     * POST /api/admin/penerbitan-sk
     * Mengunggah atau Mencatat SK Cuti Akademik yang telah ditandatangani.
     *
     * Payload yang disarankan:
     * - pengajuan_id (required|integer)
     * - admin_name (required|string|max:100)
     * - nomor_sk (nullable|string|max:100)
     * - tanggal_sk (nullable|date)
     * - sk_file (nullable|file|mimes:pdf,jpg,jpeg,png|max:5120)  // max 5MB
     * - catatan (nullable|string|max:500)
     */
    public function penerbitanSk(Request $request)
    {
        $request->validate([
            'pengajuan_id' => 'required|integer',
            'admin_name'   => 'required|string|max:100',
            'nomor_sk'     => 'nullable|string|max:100',
            'tanggal_sk'   => 'nullable|date',
            'sk_file'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'catatan'      => 'nullable|string|max:500',
        ]);

        $pengajuan = PengajuanCuti::find($request->pengajuan_id);
        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan.'], 404);
        }

        // Guard sederhana: SK umumnya dicatat setelah status "Diterbitkan SK"
        // Jika Anda ingin membolehkan dari status lain, silakan sesuaikan aturan ini.
        if (!in_array($pengajuan->status_permohonan, ['Diterbitkan SK', 'Pending Aktif Kembali'])) {
            return response()->json([
                'message' => 'SK hanya dapat dicatat/diunggah untuk pengajuan dengan status Diterbitkan SK atau Pending Aktif Kembali. Status saat ini: ' . $pengajuan->status_permohonan
            ], 400);
        }

        DB::beginTransaction();
        try {
            $storedPath = null;

            if ($request->hasFile('sk_file')) {
                $file = $request->file('sk_file');

                // Folder: storage/app/public/sk_cuti/{pengajuan_id}/
                // Pastikan sudah menjalankan: php artisan storage:link
                $storedPath = $file->store(
                    'public/sk_cuti/' . $pengajuan->id
                );
            }

            // Update kolom-kolom SK di PengajuanCuti (pastikan kolom ada di tabel Anda):
            // - sk_path (string, nullable)
            // - nomor_sk (string, nullable)
            // - tanggal_sk (date/datetime, nullable)
            // - sk_uploaded_at (datetime, nullable)
            // - sk_uploaded_by (string, nullable)
            $updatePayload = [];

            if ($storedPath !== null) {
                // simpan path "public/..." agar konsisten dengan Storage
                $updatePayload['sk_path'] = $storedPath;
            }
            if ($request->filled('nomor_sk')) {
                $updatePayload['nomor_sk'] = $request->nomor_sk;
            }
            if ($request->filled('tanggal_sk')) {
                $updatePayload['tanggal_sk'] = $request->tanggal_sk;
            }

            // Set metadata upload/catat
            $updatePayload['sk_uploaded_at'] = now();
            $updatePayload['sk_uploaded_by'] = $request->admin_name;

            if (!empty($updatePayload)) {
                $pengajuan->update($updatePayload);
            }

            LogAktivitas::create([
                'pengajuan_id'    => $pengajuan->id,
                'tipe_aktivitas'  => 'CATAT_UNGGAH_SK',
                'dilakukan_oleh'  => $request->admin_name,
                'timestamp'       => now(),
                'catatan'         => $request->catatan
                    ? ('SK dicatat/diunggah. Catatan: ' . $request->catatan)
                    : 'SK dicatat/diunggah (bertanda tangan).'
            ]);

            DB::commit();

            $response = [
                'message' => 'SK Cuti Akademik berhasil dicatat/diunggah.',
                'data' => [
                    'pengajuan_id' => $pengajuan->id,
                    'nomor_sk'     => $request->nomor_sk ?? ($pengajuan->nomor_sk ?? null),
                    'tanggal_sk'   => $request->tanggal_sk ?? ($pengajuan->tanggal_sk ?? null),
                    'sk_path'      => $storedPath ?? ($pengajuan->sk_path ?? null),
                ]
            ];

            // Jika file diunggah, kembalikan URL publik (jika storage:link aktif)
            if (!empty($response['data']['sk_path'])) {
                $response['data']['sk_url'] = \Illuminate\Support\Facades\Storage::url($response['data']['sk_path']);
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mencatat/mengunggah SK cuti.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function hapusAtauArsipCuti(Request $request, $id)
    {
        $request->validate([
            'admin_name' => 'required|string|max:100',
            'alasan'     => 'nullable|string|max:500',
        ]);

        $pengajuan = PengajuanCuti::find($id);
        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            // Prefer arsip jika model tidak memakai soft delete.
            $usesSoftDeletes = in_array(
                'Illuminate\\Database\\Eloquent\\SoftDeletes',
                class_uses_recursive($pengajuan)
            );

            if ($usesSoftDeletes) {
                $pengajuan->delete();
            } else {

                $pengajuan->update([
                    'status_permohonan' => 'Diarsipkan',
                    'diarsipkan_pada'   => now(),
                    'diarsipkan_oleh'   => $request->admin_name,
                    'alasan_arsip'      => $request->alasan,
                ]);
            }

            LogAktivitas::create([
                'pengajuan_id'   => $pengajuan->id,
                'tipe_aktivitas' => $usesSoftDeletes ? 'HAPUS_CUTI' : 'ARSIP_CUTI',
                'dilakukan_oleh' => $request->admin_name,
                'timestamp'      => now(),
                'catatan'        => $request->alasan
                    ? (($usesSoftDeletes ? 'Data cuti dihapus.' : 'Data cuti diarsipkan.') . ' Alasan: ' . $request->alasan)
                    : ($usesSoftDeletes ? 'Data cuti dihapus.' : 'Data cuti diarsipkan.')
            ]);

            DB::commit();

            return response()->json([
                'message' => $usesSoftDeletes
                    ? 'Data cuti berhasil dihapus (soft delete).'
                    : 'Data cuti berhasil diarsipkan.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus/mengarsip data cuti.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function daftarTagihanCuti()
    {
        $data = PengajuanCuti::with(['mahasiswa:id,nim,nama,prodi'])
            ->whereNotNull('biaya_cuti')
            ->where('biaya_cuti', '>', 0)
            ->orderBy('tanggal_pengajuan', 'desc')
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data tagihan biaya cuti akademik.'
            ], 404);
        }

        return response()->json([
            'message' => 'Data tagihan cuti berhasil diambil.',
            'total_data' => $data->count(),
            'data' => $data
        ], 200);
    }
}
