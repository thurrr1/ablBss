<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $table = 'PengajuanCuti';

    protected $fillable = [
        'mahasiswa_id',
        'semester_cuti',
        'lama_cuti_semester',
        'alasan_cuti',
        'tanggal_pengajuan',
        'status_permohonan',
        'tanggal_sk_terbit',
        'catatan_admin',
        'verifikator_pa_id',
        'tanggal_verifikasi_pa',
        'alasan_penolakan',
        'is_perpanjangan',
        'semester_perpanjangan',
        'alasan_perpanjangan',
    ];

    // Relasi: Satu Pengajuan Cuti hanya dimiliki oleh satu Mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    // Relasi: Satu Pengajuan Cuti bisa memiliki banyak Log Aktivitas
    public function logAktivitas()
    {
        return $this->hasMany(LogAktivitas::class, 'pengajuan_id');
    }
}