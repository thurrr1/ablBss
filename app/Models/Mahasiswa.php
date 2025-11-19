<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    // Nama tabel yang direpresentasikan oleh Model ini
    protected $table = 'Mahasiswa';

    // Kolom yang dapat diisi massal (mass assignable)
    protected $fillable = [
        'nim',
        'nama',
        'prodi',
        'dpa_id',
        'status_aktif',
    ];

    // Relasi: Satu Mahasiswa bisa memiliki banyak Pengajuan Cuti
    public function pengajuanCuti()
    {
        return $this->hasMany(PengajuanCuti::class, 'mahasiswa_id');
    }
}