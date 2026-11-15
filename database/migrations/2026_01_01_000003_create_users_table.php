<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users dipindah ke urutan setelah opd_induks/opds (bukan lagi migrasi
     * paling awal seperti bawaan Laravel), supaya opd_id/opd_induk_id bisa
     * langsung jadi FK sejak awal — tidak perlu migrasi ALTER tambahan
     * belakangan seperti di project SATU yang lama.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // NIP (Nomor Induk Pegawai) — nullable+unique: belum semua akun
            // (mis. superadmin teknis) punya NIP, tapi kalau diisi harus unik.
            // Dipakai sebagai identifier login alternatif selain email.
            $table->string('nip', 30)->nullable()->unique();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->foreignId('opd_induk_id')->nullable()->constrained('opd_induks')->nullOnDelete();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('role')->default('pengolah'); // backed enum App\Enums\UserRole
            $table->string('status')->default('verify');  // backed enum App\Enums\UserStatus
            $table->string('phone_number', 15)->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
