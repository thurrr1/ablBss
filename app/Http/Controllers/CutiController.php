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
 * @OA\Tag(
 * name="Mahasiswa",
 * description="Fungsional Layanan untuk Pengajuan Cuti Mahasiswa"
 * )
 * @OA\Tag(
 * name="Admin Global",
 * description="Fungsional Layanan untuk Laporan Global Admin"
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

    /**
     * Fungsional Layanan Global: Melihat seluruh riwayat pengajuan cuti (Semua Mahasiswa)
     * Endpoint: GET /api/admin/semua-pengajuan
     *
     * @OA\Get(
     * path="/api/admin/semua-pengajuan",
     * operationId="lihatRiwayatGlobal",
     * tags={"Admin Global"},
     * summary="Melihat seluruh riwayat pengajuan cuti semua Mahasiswa (Akses Admin)",
     * @OA\Response(
     * response=200,
     * description="Seluruh riwayat pengajuan cuti berhasil diambil.",
     * ),
     * @OA\Response(
     * response=404,
     * description="Belum ada data pengajuan cuti di database.",
     * )
     * )
     */
    public function lihatRiwayatGlobal()
    {
        $riwayat = PengajuanCuti::with('mahasiswa:id,nim,nama,prodi')
            ->with('logAktivitas')
            ->orderBy('tanggal_pengajuan', 'desc')
            ->get();

        if ($riwayat->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data pengajuan cuti di database.'
            ], 404);
        }

        return response()->json([
            'message' => 'Seluruh riwayat pengajuan cuti berhasil diambil.',
            'total_data' => $riwayat->count(),
            'data' => $riwayat
        ]);
    }

    /**
     * Fungsional 6: Daftar pengajuan pending PA (GET /api/pa/pengajuan/pending)
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

        return response()->json(['message' => 'Daftar pending PA.', 'data' => $pending]);
    }

    public function setujuiPA(Request $request, $id)
    {
        $pengajuan = PengajuanCuti::findOrFail($id);
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
                'catatan' => 'Persetujuan oleh PA.',
            ]);
            DB::commit();
            return response()->json(['message' => 'Berhasil disetujui PA.', 'data' => $pengajuan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal.', 'error' => $e->getMessage()], 500);
        }
    }

    public function tolakPA(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string']);
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
            return response()->json(['message' => 'Berhasil ditolak PA.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal.', 'error' => $e->getMessage()], 500);
        }
    }

    // --- Fungsional Admin & Laporan (14, 15) ---

    public function statistikCuti()
    {
        $statistik = PengajuanCuti::select('status_permohonan', DB::raw('count(*) as total'))
            ->groupBy('status_permohonan')->get();
        return response()->json(['data' => $statistik]);
    }

    public function perpanjangCuti(Request $request, $id)
    {
        $request->validate(['tambah_semester' => 'required|integer|min:1']);
        $pengajuan = PengajuanCuti::findOrFail($id);
        DB::beginTransaction();
        try {
            $pengajuan->update([
                'lama_cuti_semester' => $pengajuan->lama_cuti_semester + $request->tambah_semester,
                'is_perpanjangan' => true,
                'catatan_admin' => $pengajuan->catatan_admin . " | Perpanjangan +" . $request->tambah_semester . " sem."
            ]);
            LogAktivitas::create([
                'pengajuan_id' => $pengajuan->id,
                'tipe_aktivitas' => 'EXTEND_CUTI',
                'dilakukan_oleh' => 'Admin',
                'timestamp' => now(),
                'catatan' => 'Perpanjangan masa cuti.',
            ]);
            DB::commit();
            return response()->json(['message' => 'Berhasil diperpanjang.', 'data' => $pengajuan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal.', 'error' => $e->getMessage()], 500);
        }
    }
}