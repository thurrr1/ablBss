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
     * ======================================================================
     * SWAGGER / OPENAPI DOCS (L5-SWAGGER)
     * Catatan:
     * - Ini hanya penambahan dokumentasi (annotations) untuk fungsional 9–13.
     * - Tidak mengubah logika/isi kode lama.
     * - Pastikan l5-swagger Anda meng-scan folder app/Http/Controllers
     * ======================================================================
     */

    /**
     * No. 9 (READ)
     * Mendapatkan daftar seluruh mahasiswa yang sedang berstatus Cuti Akademik
     *
     * @OA\Get(
     *   path="/api/mahasiswa-cuti",
     *   tags={"Admin Cuti"},
     *   summary="(9) Daftar mahasiswa yang sedang berstatus Cuti Akademik",
     *   description="Mengambil daftar pengajuan cuti dengan status 'Diterbitkan SK' atau 'Pending Aktif Kembali', beserta data mahasiswa (nim, nama, prodi).",
     *   @OA\Response(
     *     response=200,
     *     description="Berhasil mengambil daftar mahasiswa cuti",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Daftar mahasiswa cuti berhasil diambil."),
     *       @OA\Property(property="total_data", type="integer", example=1),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="mahasiswa_id", type="integer", example=1),
     *           @OA\Property(property="semester_cuti", type="string", example="Ganjil 2026/2027"),
     *           @OA\Property(property="lama_cuti_semester", type="integer", example=1),
     *           @OA\Property(property="alasan_cuti", type="string", example="Mengambil magang di luar kota."),
     *           @OA\Property(property="tanggal_pengajuan", type="string", example="2025-12-23 12:07:52"),
     *           @OA\Property(property="status_permohonan", type="string", example="Diterbitkan SK"),
     *           @OA\Property(
     *             property="mahasiswa",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nim", type="string", example="12345678"),
     *             @OA\Property(property="nama", type="string", example="Budi Santoso"),
     *             @OA\Property(property="prodi", type="string", example="Teknik Informatika")
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Tidak ada data mahasiswa cuti",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Tidak ada mahasiswa yang sedang berstatus Cuti Akademik.")
     *     )
     *   )
     * )
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
     *
     * @OA\Put(
     *   path="/api/status-cuti/{id}",
     *   tags={"Admin Cuti"},
     *   summary="(10) Terbitkan SK: ubah status dari 'Disetujui PA' menjadi 'Diterbitkan SK'",
     *   description="Mengubah status_permohonan pengajuan cuti menjadi 'Diterbitkan SK'. Hanya dapat dilakukan jika status saat ini 'Disetujui PA'.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID pengajuan cuti",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"admin_name"},
     *       @OA\Property(property="admin_name", type="string", maxLength=100, example="Admin BAAK")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Status berhasil diubah",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Status cuti berhasil diubah menjadi Diterbitkan SK.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Status tidak memenuhi syarat perubahan",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Status tidak dapat diubah. Status saat ini: Pending PA")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Pengajuan tidak ditemukan",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Pengajuan cuti tidak ditemukan.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Kesalahan server",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Gagal menerbitkan SK cuti."),
     *       @OA\Property(property="error", type="string", example="Exception message")
     *     )
     *   )
     * )
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
     * POST /api/penerbitan-sk
     * Mengunggah atau Mencatat SK Cuti Akademik yang telah ditandatangani.
     *
     * @OA\Post(
     *   path="/api/penerbitan-sk",
     *   tags={"Admin Cuti"},
     *   summary="(11) Mengunggah atau mencatat SK Cuti Akademik",
     *   description="Mencatat metadata SK dan/atau mengunggah file SK (pdf/jpg/jpeg/png max 5MB). Hanya untuk status 'Diterbitkan SK' atau 'Pending Aktif Kembali'.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"pengajuan_id","admin_name"},
     *         @OA\Property(property="pengajuan_id", type="integer", example=1),
     *         @OA\Property(property="admin_name", type="string", maxLength=100, example="Admin BAAK"),
     *         @OA\Property(property="nomor_sk", type="string", nullable=true, maxLength=100, example="SK-BAAK/001/2026"),
     *         @OA\Property(property="tanggal_sk", type="string", format="date", nullable=true, example="2026-01-02"),
     *         @OA\Property(property="catatan", type="string", nullable=true, maxLength=500, example="SK sudah ditandatangani dan dicatat."),
     *         @OA\Property(
     *           property="sk_file",
     *           type="string",
     *           format="binary",
     *           nullable=true,
     *           description="File SK (pdf/jpg/jpeg/png), maksimum 5MB"
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Berhasil dicatat/diunggah",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="SK Cuti Akademik berhasil dicatat/diunggah."),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="pengajuan_id", type="integer", example=1),
     *         @OA\Property(property="nomor_sk", type="string", nullable=true, example="SK-BAAK/001/2026"),
     *         @OA\Property(property="tanggal_sk", type="string", nullable=true, example="2026-01-02"),
     *         @OA\Property(property="sk_path", type="string", nullable=true, example=null),
     *         @OA\Property(property="sk_url", type="string", nullable=true, example=null)
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Status pengajuan tidak sesuai",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="SK hanya dapat dicatat/diunggah untuk pengajuan dengan status Diterbitkan SK atau Pending Aktif Kembali. Status saat ini: Pending PA")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Pengajuan tidak ditemukan",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Pengajuan cuti tidak ditemukan.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Kesalahan server",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Gagal mencatat/mengunggah SK cuti."),
     *       @OA\Property(property="error", type="string", example="Exception message")
     *     )
     *   )
     * )
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

    /**
     * No. 12 (DELETE)
     * Menghapus (atau Mengarsip) data Cuti yang tidak valid atau telah usang.
     *
     * @OA\Delete(
     *   path="/api/status-cuti/{id}",
     *   tags={"Admin Cuti"},
     *   summary="(12) Menghapus atau mengarsip data cuti",
     *   description="Menghapus data cuti (soft delete jika menggunakan SoftDeletes) atau mengarsipkan dengan mengubah status menjadi 'Diarsipkan' serta mencatat alasan.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID pengajuan cuti",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"admin_name"},
     *       @OA\Property(property="admin_name", type="string", maxLength=100, example="Admin BAAK"),
     *       @OA\Property(property="alasan", type="string", nullable=true, maxLength=500, example="Data uji coba (testing endpoint delete/arsip).")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Berhasil dihapus/diarsipkan",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Data cuti berhasil diarsipkan.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Pengajuan tidak ditemukan",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Pengajuan cuti tidak ditemukan.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Kesalahan server",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Gagal menghapus/mengarsip data cuti."),
     *       @OA\Property(property="error", type="string", example="Exception message")
     *     )
     *   )
     * )
     */
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

    /**
     * No. 13 (READ)
     * Mendapatkan data tagihan biaya Cuti Akademik (jika ada).
     *
     * @OA\Get(
     *   path="/api/tagihan-cuti",
     *   tags={"Admin Cuti"},
     *   summary="(13) Mendapatkan data tagihan biaya Cuti Akademik",
     *   description="Mengambil daftar pengajuan cuti yang memiliki biaya_cuti > 0 (jika kolom biaya_cuti digunakan).",
     *   @OA\Response(
     *     response=200,
     *     description="Berhasil mengambil data tagihan cuti",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Data tagihan cuti berhasil diambil."),
     *       @OA\Property(property="total_data", type="integer", example=3),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="mahasiswa_id", type="integer", example=1),
     *           @OA\Property(property="semester_cuti", type="string", example="Ganjil 2026/2027"),
     *           @OA\Property(property="status_permohonan", type="string", example="Pending PA"),
     *           @OA\Property(property="biaya_cuti", type="number", nullable=true, example=1500000),
     *           @OA\Property(
     *             property="mahasiswa",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nim", type="string", example="12345678"),
     *             @OA\Property(property="nama", type="string", example="Budi Santoso"),
     *             @OA\Property(property="prodi", type="string", example="Teknik Informatika")
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Tidak ada data tagihan",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Tidak ada data tagihan biaya cuti akademik.")
     *     )
     *   )
     * )
     */
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
