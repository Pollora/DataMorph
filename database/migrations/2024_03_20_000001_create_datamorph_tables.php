<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('datamorph_logs')) {
            Schema::create('datamorph_logs', function (Blueprint $table) {
                $table->id();
                $table->string('pipeline');
                $table->integer('processed_rows');
                $table->float('execution_time');
                $table->string('status');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('datamorph_errors')) {
            Schema::create('datamorph_errors', function (Blueprint $table) {
                $table->id();
                $table->string('pipeline');
                $table->text('error_message');
                $table->json('failed_data')->nullable();
                $table->string('stage')->comment('extract, transform, or load');
                $table->integer('batch_number')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datamorph_logs');
        Schema::dropIfExists('datamorph_errors');
    }
}; 