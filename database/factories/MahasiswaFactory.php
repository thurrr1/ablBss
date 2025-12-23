<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class MahasiswaFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     */
    protected $model = Mahasiswa::class;

    /**
     * Definisikan state default dari model.
     */
    public function definition(): array
    {
        return [
            // Generate NIM acak 8-10 digit
            'nim' => $this->faker->unique()->numerify('#########'), 
            
            // Generate nama lengkap
            'nama' => $this->faker->name(),
            
            // Pilih prodi secara acak
            'prodi' => $this->faker->randomElement([
                'Teknik Informatika', 
                'Sistem Informasi', 
                'Desain Komunikasi Visual', 
                'Manajemen', 
                'Akuntansi'
            ]),
            
            // ID Dosen PA (Asumsi ID 1-5 ada di tabel User/Dosen)
            'dpa_id' => $this->faker->numberBetween(1, 5), 
            
            // Status default
            'status_aktif' => 'Aktif',
            
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}