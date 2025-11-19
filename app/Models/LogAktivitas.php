<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'LogAktivitas';

    protected $fillable = [
        'pengajuan_id',
        'tipe_aktivitas',
        'dilakukan_oleh',
        'timestamp',
        'catatan',
    ];

    // Relasi: Satu Log Aktivitas merujuk ke satu Pengajuan Cuti
    public function pengajuanCuti()
    {
        return $this->belongsTo(PengajuanCuti::class, 'pengajuan_id');
    }
}