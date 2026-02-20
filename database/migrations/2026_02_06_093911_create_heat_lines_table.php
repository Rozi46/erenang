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
        Schema::create('db_heat_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('heat_id')->constrained('heats')->onDelete('cascade');
            $table->foreignUuid('athlete_id')->constrained('athletes')->onDelete('cascade');
            $table->integer('line_number');
            $table->string('best_time')->nullable();
            $table->string('hasil')->nullable();
            $table->integer('ranking')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_heat_lines');
    }
};
