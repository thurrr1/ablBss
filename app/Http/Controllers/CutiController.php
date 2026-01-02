<?php

namespace App\Http\Controllers;

use App\Models\PengajuanCuti;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Digunakan untuk Transaction

/**
 * @OA\Info(
 * version="1.0.0",
 * title="ABL BSS API Documentation",
 * description="Dokumentasi API untuk Pengelolaan Berhenti Sementara Studi (BSS)",
 * @OA\Contact(
 * email="abl.bss@example.com"
 * )
 * )
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Server Lokal Development"
 * )
 * @OA\Tag(name="Authentication", description="Endpoint untuk masuk ke sistem")
 * @OA\Tag(name="Mahasiswa", description="Fungsional Layanan untuk Pengajuan Cuti Mahasiswa")
 * @OA\Tag(name="Admin Global", description="Fungsional Layanan untuk Laporan Global Admin")
 *
 * @OA\SecurityScheme(
 *      securityScheme="csrfHeader",
 *      type="apiKey",
 *      in="header",
 *      name="X-XSRF-TOKEN",
 *      description="Untuk otentikasi CSRF, token diambil dari cookie 'XSRF-TOKEN' dan dikirim melalui header ini. Wajib untuk request POST, PUT, DELETE."
 * )
 */
class CutiController extends Controller
{
    /**
     * Fungsional Layanan 1: Mengajukan permohonan Cuti Akademik baru (POST /api/pengajuan-cuti)
     * @OA\Post(
     * path="/api/pengajuan-cuti",
     * operationId="ajukanCuti",
     * tags={"Mahasiswa"},
     * summary="Mengajukan permohonan Cuti Akademik baru",
     * security={ {"csrfHeader": {}} },
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"mahasiswa_id", "semester_cuti", "lama_cuti_semester", "alasan_cuti"},
     * @OA\Property(property="mahasiswa_id", type="integer", example=1, description="ID Mahasiswa yang mengajukan"),
     * @OA\Property(property="semester_cuti", type="string", example="Genap 2025/2026", description="Semester cuti yang diajukan"),
     * @OA\Property(property="lama_cuti_semester", type="integer", example=1, description="Lama cuti dalam semester"),
     * @OA\Property(property="alasan_cuti", type="string", example="Mengambil magang di luar kota.", description="Alasan detail pengajuan cuti")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Pengajuan cuti berhasil dibuat. Menunggu verifikasi PA.",
     * ),
     * @OA\Response(
     * response=422,
     * description="Validasi data gagal (misal: mahasiswa_id tidak valid).",
     * )
     * )
     */
    public function ajukanCuti(Request $request)
    {
        // 1. Validasi Data
        $request->validate([
            'mahasiswa_id' => 'required|integer|exists:Mahasiswa,id',
            'semester_cuti' => 'required|string|max:50',
            'lama_cuti_semester' => 'required|integer|min:1',
            'alasan_cuti' => 'required|string|max:500',
        ]);
        
        DB::beginTransaction();

        try {
            // 2. CREATE: Simpan data Pengajuan Cuti
            $pengajuan = PengajuanCuti::create([
                'mahasiswa_id' => $request->mahasiswa_id,
                'semester_cuti' => $request->semester_cuti,
                'lama_cuti_semester' => $request->lama_cuti_semester,
                'alasan_cuti' => $request->alasan_cuti,
                'tanggal_pengajuan' => now(), 
                'status_permohonan' => 'Pending PA', 
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
            ], 201); 

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuat pengajuan cuti.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fungsional Layanan 2: Melihat detail status satu permohonan Cuti Akademik (GET /api/pengajuan-cuti/{id})
     *
     * @OA\Get(
     * path="/api/pengajuan-cuti/{id}",
     * operationId="lihatDetailPengajuan",
     * tags={"Mahasiswa"},
     * summary="Melihat detail status satu permohonan Cuti",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID Pengajuan Cuti",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Detail pengajuan cuti berhasil diambil.",
     * ),
     * @OA\Response(
     * response=404,
     * description="Detail pengajuan cuti tidak ditemukan.",
     * )
     * )
     */
    public function lihatDetailPengajuan($id)
    {
        // Cari pengajuan cuti berdasarkan ID, beserta relasi Mahasiswa dan Log Aktivitas
        $pengajuan = PengajuanCuti::with(['mahasiswa:id,nim,nama', 'logAktivitas'])
                                 ->find($id);

        if (!$pengajuan) {
            return response()->json(['message' => 'Detail pengajuan cuti tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Detail pengajuan cuti berhasil diambil.',
            'data' => $pengajuan
        ]);
    }

    /**
     * Fungsional Layanan 3: Melihat seluruh riwayat pengajuan Cuti Mahasiswa (GET /api/pengajuan-cuti/riwayat/{mahasiswaId})
     * FUNGSI INI SUDAH DIPERBAIKI MENGGUNAKAN PARAMETER ROUTE
     *
     * @OA\Get(
     * path="/api/pengajuan-cuti/riwayat/{mahasiswaId}",
     * operationId="lihatRiwayatMahasiswa",
     * tags={"Mahasiswa"},
     * summary="Melihat seluruh riwayat pengajuan Cuti Mahasiswa yang bersangkutan",
     * @OA\Parameter(
     * name="mahasiswaId",
     * in="path",
     * required=true,
     * description="ID Mahasiswa (diambil dari URL)",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Riwayat pengajuan cuti berhasil diambil.",
     * ),
     * @OA\Response(
     * response=404,
     * description="Riwayat tidak ditemukan.",
     * )
     * )
     */
    public function lihatRiwayatMahasiswa($mahasiswaId)
    {
        // Langsung menggunakan ID Mahasiswa dari Route Parameter
        $riwayat = PengajuanCuti::where('mahasiswa_id', $mahasiswaId)
            ->with(['mahasiswa:id,nim,nama,prodi', 'logAktivitas']) 
            ->orderBy('tanggal_pengajuan', 'desc') 
            ->get();

        if ($riwayat->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ditemukan riwayat pengajuan cuti untuk Mahasiswa ID ' . $mahasiswaId,
            ], 404);
        }

        return response()->json([
            'message' => 'Riwayat pengajuan cuti berhasil diambil.',
            'data' => $riwayat
        ]);
    }

    /**
     * Fungsional Layanan 4: Membatalkan pengajuan Cuti Akademik (PUT /api/pengajuan-cuti/{id}/batal)
     *
     * @OA\Put(
     * path="/api/pengajuan-cuti/{id}/batal",
     * operationId="batalkanPengajuan",
     * tags={"Mahasiswa"},
     * summary="Membatalkan pengajuan cuti yang masih Pending PA",
     * security={ {"csrfHeader": {}} },
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID Pengajuan Cuti",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"mahasiswa_id"},
     * @OA\Property(property="mahasiswa_id", type="integer", example=1, description="ID Mahasiswa yang melakukan pembatalan")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Pengajuan cuti berhasil dibatalkan.",
     * ),
     * @OA\Response(
     * response=400,
     * description="Pengajuan tidak dapat dibatalkan karena statusnya sudah berubah.",
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden: Anda tidak berhak membatalkan pengajuan ini.",
     * )
     * )
     */
    public function batalkanPengajuan(Request $request, $id)
    {
        $request->validate(['mahasiswa_id' => 'required|integer']); 
        
        $pengajuan = PengajuanCuti::find($id);

        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan.'], 404);
        }
        
        // Perbaikan: Pastikan Mahasiswa yang membatalkan adalah Mahasiswa pengaju
        if ($pengajuan->mahasiswa_id !== $request->mahasiswa_id) {
            return response()->json(['message' => 'Anda tidak berhak membatalkan pengajuan ini.'], 403);
        }
        
        // Hanya bisa dibatalkan jika statusnya masih 'Pending PA'
        if ($pengajuan->status_permohonan !== 'Pending PA') {
            return response()->json(['message' => 'Pengajuan tidak dapat dibatalkan karena statusnya: ' . $pengajuan->status_permohonan], 400);
        }

        DB::beginTransaction();
        try {
            // UPDATE: Ubah status menjadi Dibatalkan
            $pengajuan->update([
                'status_permohonan' => 'Dibatalkan Mahasiswa',
            ]);

            // CREATE: Simpan Log Aktivitas
            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'DIBATALKAN',
                'dilakukan_oleh' => 'Mahasiswa (ID: ' . $pengajuan->mahasiswa_id . ')', 
                'timestamp' => now(),
                'catatan' => 'Pengajuan dibatalkan oleh Mahasiswa.',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pengajuan cuti ID ' . $id . ' berhasil dibatalkan.',
                'data' => $pengajuan
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan pengajuan.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fungsional Layanan 5: Mengajukan permohonan untuk Aktif Kembali (POST /api/aktif-kembali)
     *
     * @OA\Post(
     * path="/api/aktif-kembali",
     * operationId="ajukanAktifKembali",
     * tags={"Mahasiswa"},
     * summary="Mengajukan permohonan untuk Aktif Kembali setelah masa cuti",
     * security={ {"csrfHeader": {}} },
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"pengajuan_id", "semester_aktif"},
     * @OA\Property(property="pengajuan_id", type="integer", example=1, description="ID Pengajuan cuti yang terkait"),
     * @OA\Property(property="semester_aktif", type="string", example="Gasal 2026/2027", description="Semester yang diajukan untuk aktif kembali")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Permintaan aktif kembali berhasil diajukan. Menunggu verifikasi BAAK.",
     * ),
     * @OA\Response(
     * response=400,
     * description="Pengajuan cuti belum disetujui atau sudah kadaluarsa.",
     * )
     * )
     */
    public function ajukanAktifKembali(Request $request)
    {
        $request->validate([
            'pengajuan_id' => 'required|integer|exists:PengajuanCuti,id',
            'semester_aktif' => 'required|string|max:50',
        ]);

        $pengajuan = PengajuanCuti::findOrFail($request->pengajuan_id);
        
        // Perbaikan: Pastikan pengajuan cuti yang diajukan untuk aktif kembali sudah 'Diterbitkan SK'
        if ($pengajuan->status_permohonan !== 'Diterbitkan SK') {
             return response()->json(['message' => 'Cuti belum disetujui atau sudah kadaluarsa.'], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Catat Permintaan Log Aktivitas
            LogAktivitas::create([
                'pengajuan_id' => $request->pengajuan_id,
                'tipe_aktivitas' => 'REQUEST_AKTIF_KEMBALI',
                'dilakukan_oleh' => 'Mahasiswa (ID: ' . $pengajuan->mahasiswa_id . ')',
                'timestamp' => now(),
                'catatan' => 'Permintaan aktif kembali untuk semester ' . $request->semester_aktif . '.',
            ]);
            
            // 2. Update Status Pengajuan menjadi Pending Aktif Kembali
            $pengajuan->update(['status_permohonan' => 'Pending Aktif Kembali']);
            
            DB::commit();

            return response()->json([
                'message' => 'Permintaan aktif kembali berhasil diajukan. Menunggu verifikasi BAAK.',
                'request_data' => $request->all()
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengajukan aktif kembali.', 'error' => $e->getMessage()], 500);
        }
    }


  // =========================================================================
    // II. Fungsional Layanan Verifikasi (Untuk Dosen Pembimbing Akademik / PA)
    // =========================================================================

    /**
     * Fungsional Layanan 6: Mendapatkan daftar pengajuan Cuti yang perlu diverifikasi oleh PA
     * * @OA\Get(
     * path="/api/pa/pengajuan/pending",
     * operationId="listPendingPA",
     * tags={"Dosen PA"},
     * summary="Mendapatkan daftar pengajuan cuti status Pending PA",
     * description="Mengambil semua data pengajuan cuti mahasiswa bimbingan yang menunggu verifikasi.",
     * @OA\Parameter(
     * name="PA-ID",
     * in="header",
     * required=true,
     * description="ID Dosen PA yang sedang login (Simulasi)",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Daftar pengajuan berhasil diambil.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Daftar pengajuan cuti menunggu verifikasi PA berhasil diambil."),
     * @OA\Property(property="data", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     */
    public function listPendingPA(Request $request)
    {
        $paId = $request->header('PA-ID'); 

        $pending = PengajuanCuti::where('status_permohonan', 'Pending PA')
            ->whereHas('mahasiswa', function($query) use ($paId) {
                if($paId) $query->where('dpa_id', $paId);
            })
            ->with('mahasiswa:id,nim,nama,prodi')
            ->get();

        return response()->json([
            'message' => 'Daftar pengajuan cuti menunggu verifikasi PA berhasil diambil.',
            'data' => $pending
        ]);
    }

    /**
     * Fungsional Layanan 7: Menyetujui permohonan Cuti Mahasiswa
     * * @OA\Put(
     * path="/api/pa/pengajuan/{id}/setujui",
     * operationId="setujuiPA",
     * tags={"Dosen PA"},
     * summary="Menyetujui permohonan cuti (Approve)",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID Pengajuan Cuti",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Parameter(
     * name="PA-ID",
     * in="header",
     * required=true,
     * description="ID Dosen PA yang melakukan persetujuan",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Permohonan berhasil disetujui.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Permohonan cuti disetujui oleh PA."),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Gagal: Status pengajuan bukan Pending PA."
     * )
     * )
     */
    public function setujuiPA(Request $request, $id)
    {
        $pengajuan = PengajuanCuti::findOrFail($id);

        if ($pengajuan->status_permohonan !== 'Pending PA') {
            return response()->json(['message' => 'Pengajuan tidak dalam status Pending PA.'], 400);
        }

        DB::beginTransaction();
        try {
            $pengajuan->update([
                'status_permohonan' => 'Disetujui PA',
                'verifikator_pa_id' => $request->header('PA-ID'),
                'tanggal_verifikasi_pa' => now()
            ]);

            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'APPROVE_PA',
                'dilakukan_oleh' => 'Dosen PA',
                'timestamp' => now(),
                'catatan' => 'Dosen PA menyetujui permohonan cuti.',
            ]);

            DB::commit();
            return response()->json(['message' => 'Permohonan cuti disetujui oleh PA.', 'data' => $pengajuan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyetujui permohonan.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fungsional Layanan 8: Menolak permohonan Cuti
     * * @OA\Put(
     * path="/api/pa/pengajuan/{id}/tolak",
     * operationId="tolakPA",
     * tags={"Dosen PA"},
     * summary="Menolak permohonan cuti (Reject)",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID Pengajuan Cuti",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Parameter(
     * name="PA-ID",
     * in="header",
     * required=true,
     * description="ID Dosen PA yang menolak",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"catatan"},
     * @OA\Property(property="catatan", type="string", example="Dokumen kurang lengkap, mohon lengkapi surat dokter.", description="Alasan penolakan")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Permohonan berhasil ditolak.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Permohonan cuti ditolak oleh PA.")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Validasi gagal (Catatan wajib diisi)."
     * )
     * )
     */
    public function tolakPA(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string|max:255']);
        
        $pengajuan = PengajuanCuti::findOrFail($id);

        DB::beginTransaction();
        try {
            $pengajuan->update([
                'status_permohonan' => 'Ditolak PA',
                'alasan_penolakan' => $request->catatan,
                'verifikator_pa_id' => $request->header('PA-ID'),
                'tanggal_verifikasi_pa' => now()
            ]);

            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'REJECT_PA',
                'dilakukan_oleh' => 'Dosen PA',
                'timestamp' => now(),
                'catatan' => $request->catatan,
            ]);

            DB::commit();
            return response()->json(['message' => 'Permohonan cuti ditolak oleh PA.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menolak permohonan.', 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // IV. Fungsional Layanan Pelaporan & Perpanjangan
    // =========================================================================

    /**
     * Fungsional Layanan 14: Mendapatkan data statistik Cuti
     * * @OA\Get(
     * path="/api/report/statistik-cuti",
     * operationId="statistikCuti",
     * tags={"Admin Global"},
     * summary="Melihat statistik jumlah pengajuan cuti berdasarkan status",
     * @OA\Response(
     * response=200,
     * description="Statistik berhasil diambil.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Statistik pengajuan cuti berhasil diambil."),
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="status_permohonan", type="string", example="Pending PA"),
     * @OA\Property(property="total", type="integer", example=5)
     * ))
     * )
     * )
     * )
     */
    public function statistikCuti()
    {
        $statistik = PengajuanCuti::select('status_permohonan', DB::raw('count(*) as total'))
            ->groupBy('status_permohonan')
            ->get();

        return response()->json([
            'message' => 'Statistik pengajuan cuti berhasil diambil.',
            'data' => $statistik
        ]);
    }

    /**
     * Fungsional Layanan 15: Memproses perpanjangan masa cuti
     * * @OA\Put(
     * path="/api/admin/perpanjang-cuti/{id}",
     * operationId="perpanjangCuti",
     * tags={"Admin Global"},
     * summary="Memperpanjang durasi cuti (Admin)",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID Pengajuan Cuti",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"tambah_semester"},
     * @OA\Property(property="tambah_semester", type="integer", example=1, description="Jumlah semester yang ditambahkan")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Masa cuti berhasil diperpanjang.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Masa cuti berhasil diperpanjang."),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Gagal: Status pengajuan belum Diterbitkan SK."
     * )
     * )
     */
    public function perpanjangCuti(Request $request, $id)
    {
        $request->validate(['tambah_semester' => 'required|integer|min:1']);
        
        $pengajuan = PengajuanCuti::findOrFail($id);

        if ($pengajuan->status_permohonan !== 'Diterbitkan SK') {
            return response()->json(['message' => 'Hanya pengajuan dengan SK diterbitkan yang dapat diperpanjang.'], 400);
        }

        DB::beginTransaction();
        try {
            $lama_baru = $pengajuan->lama_cuti_semester + $request->tambah_semester;
            
            $pengajuan->update([
                'lama_cuti_semester' => $lama_baru,
                'is_perpanjangan' => true,
                'catatan_admin' => $pengajuan->catatan_admin . " | Perpanjangan +" . $request->tambah_semester . " semester."
            ]);

            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'EXTEND_CUTI',
                'dilakukan_oleh' => 'Admin BAAK',
                'timestamp' => now(),
                'catatan' => 'Admin memperpanjang masa cuti sebanyak ' . $request->tambah_semester . ' semester.',
            ]);

            DB::commit();
            return response()->json(['message' => 'Masa cuti berhasil diperpanjang.', 'data' => $pengajuan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperpanjang cuti.', 'error' => $e->getMessage()], 500);
        }
    }
}
