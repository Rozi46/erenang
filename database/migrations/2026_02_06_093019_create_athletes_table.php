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
        Schema::create('db_athletes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->enum('gender', ['PA', 'PI']);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('foto')->nullable();
            $table->foreignUuid('club_id')->constrained('clubs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_athletes');
    }
};
