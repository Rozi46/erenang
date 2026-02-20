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

            $table->string('code_data', 50)->index();
            $table->string('code_heatline', 50)->nullable();
            $table->string('code_athlete', 50)->nullable();
            $table->string('code_event', 50)->nullable();

            $table->string('hasil', 20)->nullable();   // misal: 00:59.32
            $table->string('catatan', 10)->nullable(); // DNF, DSQ, NS

            $table->integer('ranking')->nullable();

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
