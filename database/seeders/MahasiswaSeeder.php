<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

class MahasiswaSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        // 1. Buat 1 Mahasiswa Spesifik (untuk testing login/manual)
        Mahasiswa::create([
            'nim' => '12345678',
            'nama' => 'Budi Santoso',
            'prodi' => 'Teknik Informatika',
            'dpa_id' => 1,
            'status_aktif' => 'Aktif',
        ]);

        // 2. Buat 50 Mahasiswa Dummy menggunakan Factory
        Mahasiswa::factory(50)->create();
    }
}