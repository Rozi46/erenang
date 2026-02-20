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
        Schema::create('db_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_event')->unique();
            $table->string('nama_event');
            $table->enum('gaya', ['bebas', 'dada', 'punggung', 'kupu-kupu', 'medley']);
            $table->integer('jarak');
            $table->string('kategori'); // contoh: KU 1, KU 2, Senior
            $table->enum('gender', ['PA', 'PI']);
            $table->date('tanggal_event');
            $table->foreignUuid('championship_id')->constrained('championships')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_events');
    }
};
