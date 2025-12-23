<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi untuk menambah kolom fungsional 6, 7, 8, dan 15.
     */
    public function up(): void {
        // Pastikan nama tabel 'PengajuanCuti' sesuai dengan yang ada di database Anda
        Schema::table('PengajuanCuti', function (Blueprint $table) {
            // Kolom Verifikasi PA (Fungsional 7 & 8)
            $table->unsignedBigInteger('verifikator_pa_id')->nullable()->after('status_permohonan');
            $table->timestamp('tanggal_verifikasi_pa')->nullable()->after('verifikator_pa_id');
            $table->text('alasan_penolakan')->nullable()->after('tanggal_verifikasi_pa');

            // Kolom Perpanjangan Cuti (Fungsional 15)
            $table->boolean('is_perpanjangan')->default(false)->after('alasan_penolakan');
            $table->string('semester_perpanjangan')->nullable()->after('is_perpanjangan');
            $table->text('alasan_perpanjangan')->nullable()->after('semester_perpanjangan');
        });
    }

    /**
     * Batalkan perubahan jika migrasi di-rollback.
     */
    public function down(): void {
        Schema::table('PengajuanCuti', function (Blueprint $table) {
            $table->dropColumn([
                'verifikator_pa_id', 
                'tanggal_verifikasi_pa', 
                'alasan_penolakan',
                'is_perpanjangan', 
                'semester_perpanjangan', 
                'alasan_perpanjangan'
            ]);
        });
    }
};