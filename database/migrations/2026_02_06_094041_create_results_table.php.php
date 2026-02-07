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
        Schema::create('db_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('athlete_id')->constrained('athletes')->onDelete('cascade');
            $table->foreignUuid('event_id')->constrained('events')->onDelete('cascade');
            $table->string('hasil')->nullable();
            $table->integer('ranking')->nullable();
            $table->string('catatan')->nullable(); // DNF, DSQ, NS
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_results');
    }
};
