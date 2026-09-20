<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_jamaah', function (Blueprint $table) {
            $table->string('kode_jamaah')->nullable()->unique()->after('id');
            $table->date('tanggal_daftar')->nullable()->after('kode_jamaah');
            $table->string('jenis_ibadah', 10)->nullable()->after('tanggal_daftar');
            $table->string('no_kk', 16)->nullable()->after('no_ktp');
            $table->string('desa_kelurahan')->nullable()->after('alamat');
            $table->string('kecamatan')->nullable()->after('desa_kelurahan');
            $table->string('kabupaten_kota')->nullable()->after('kecamatan');
            $table->string('provinsi')->nullable()->after('kabupaten_kota');
            $table->string('kode_pos', 10)->nullable()->after('provinsi');
            $table->string('email')->nullable()->after('no_hp');
            $table->string('status_pernikahan')->nullable()->after('nama_ibu');
            $table->string('pendidikan')->nullable()->after('status_pernikahan');
            $table->string('pekerjaan')->nullable()->after('pendidikan');
            $table->boolean('sudah_memiliki_paspor')->default(false)->after('pekerjaan');
            $table->string('gol_darah', 3)->nullable()->after('city_passport');
            $table->text('kebutuhan_khusus')->nullable()->after('gol_darah');
            $table->string('nama_kontak_darurat')->nullable()->after('kebutuhan_khusus');
            $table->string('hubungan_kontak_darurat')->nullable()->after('nama_kontak_darurat');
            $table->string('no_hp_darurat')->nullable()->after('hubungan_kontak_darurat');
            $table->date('tanggal_keberangkatan')->nullable()->after('paket_id');
            $table->string('status_pendaftaran')->default('Terdaftar')->after('tanggal_keberangkatan');
            $table->string('status_pembayaran')->nullable()->after('status_pendaftaran');
            $table->text('keterangan')->nullable()->after('status_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('t_jamaah', function (Blueprint $table) {
            $table->dropUnique(['kode_jamaah']);
            $table->dropColumn([
                'kode_jamaah',
                'tanggal_daftar',
                'jenis_ibadah',
                'no_kk',
                'desa_kelurahan',
                'kecamatan',
                'kabupaten_kota',
                'provinsi',
                'kode_pos',
                'email',
                'status_pernikahan',
                'pendidikan',
                'pekerjaan',
                'sudah_memiliki_paspor',
                'gol_darah',
                'kebutuhan_khusus',
                'nama_kontak_darurat',
                'hubungan_kontak_darurat',
                'no_hp_darurat',
                'tanggal_keberangkatan',
                'status_pendaftaran',
                'status_pembayaran',
                'keterangan',
            ]);
        });
    }
};