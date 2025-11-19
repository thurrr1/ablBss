<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Mahasiswa
        Schema::create('Mahasiswa', function (Blueprint $table) {
            $table->id(); // Membuat kolom 'id' sebagai PK, Auto-Increment
            $table->string('nim')->unique();
            $table->string('nama');
            $table->string('prodi')->nullable();
            $table->unsignedBigInteger('dpa_id')->nullable();
            $table->string('status_aktif')->default('Aktif');
            $table->timestamps(); // created_at dan updated_at
        });

        // 2. Tabel PengajuanCuti
        Schema::create('PengajuanCuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('Mahasiswa'); // Foreign Key ke Mahasiswa
            $table->string('semester_cuti');
            $table->integer('lama_cuti_semester');
            $table->text('alasan_cuti')->nullable();
            $table->dateTime('tanggal_pengajuan');
            $table->string('status_permohonan')->default('Pending PA');
            $table->date('tanggal_sk_terbit')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
        
        // 3. Tabel LogAktivitas
        Schema::create('LogAktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('PengajuanCuti'); // Foreign Key ke PengajuanCuti
            $table->string('tipe_aktivitas');
            $table->string('dilakukan_oleh');
            $table->dateTime('timestamp');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('LogAktivitas');
        Schema::dropIfExists('PengajuanCuti');
        Schema::dropIfExists('Mahasiswa');
    }
};