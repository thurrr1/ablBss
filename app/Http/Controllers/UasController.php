<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * @OA\Tag(name="UAS", description="Fungsional UAS - Mengonsumsi Public API TheMealDB")
 */
class UasController extends Controller
{
    private $apiKey = "1"; // Sesuai instruksi web TheMealDB untuk edukasi
    private $baseUrl = "https://www.themealdb.com/api/json/v1";

    /**
     * @OA\Get(
     * path="/api/uas/makanan/kategori",
     * operationId="getMealCategories",
     * tags={"UAS"},
     * summary="UAS 1: List Kategori Makanan (Mengonsumsi TheMealDB)",
     * @OA\Response(response=200, description="Berhasil mengambil daftar kategori")
     * )
     */
    public function listKategori()
    {
        // 1. Ambil data mentah dari API luar
        $response = Http::get("{$this->baseUrl}/{$this->apiKey}/categories.php");

        if ($response->failed()) {
            return response()->json(['message' => 'Gagal mengambil data dari server pusat'], 502);
        }

        $dataMentah = $response->json()['categories'] ?? [];

        // 2. PROSES PENGOLAHAN (Mengonsumsi & Mengolah)
        // Kita hanya ambil ID, Nama, dan Gambar saja (Sesuai kebutuhan aplikasi kita)
        $dataOlahan = collect($dataMentah)->map(function ($item) {
            return [
                'id_kategori' => $item['idCategory'],
                'nama_menu'   => $item['strCategory'],
                'foto'        => $item['strCategoryThumb'],
                'keterangan'  => str($item['strCategoryDescription'])->limit(100), // Kita potong keterangannya agar tidak kepanjangan
            ];
        });

        // 3. Kirim data yang sudah rapi
        return response()->json([
            'sumber' => 'TheMealDB',
            'total'  => count($dataOlahan),
            'hasil'  => $dataOlahan
        ]);
    }

    /**
     * @OA\Get(
     * path="/api/uas/makanan/cari",
     * operationId="searchMeal",
     * tags={"UAS"},
     * summary="UAS 2: Detail Makanan berdasarkan Nama (Mengonsumsi TheMealDB)",
     * @OA\Parameter(
     * name="s",
     * in="query",
     * required=true,
     * description="Nama makanan (contoh: Arrabiata)",
     * @OA\Schema(type="string")
     * ),
     * @OA\Response(response=200, description="Berhasil mengambil detail makanan"),
     * @OA\Response(response=404, description="Makanan tidak ditemukan")
     * )
     */
    public function detailMakanan(Request $request)
    {
        $nama = $request->query('s');

        // Mengonsumsi API luar (Detail)
        $response = Http::get("{$this->baseUrl}/{$this->apiKey}/search.php", [
            's' => $nama
        ]);

        return response()->json([
            'sumber' => 'TheMealDB API',
            'query_nama' => $nama,
            'data' => $response->json()['meals'] ?? 'Tidak ditemukan'
        ]);
    }
}