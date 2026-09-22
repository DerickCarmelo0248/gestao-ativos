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
        Schema::create('asset_movements', function (Blueprint $table) {
    $table->id();

    $table->foreignId('asset_id')
        ->constrained()
        ->restrictOnDelete();

    $table->foreignId('user_id')
        ->constrained()
        ->restrictOnDelete();

    $table->foreignId('unit_id')
        ->constrained()
        ->restrictOnDelete();

    $table->uuid('batch_id')->index();
    $table->string('type', 30);
    $table->text('notes')->nullable();
    $table->timestampsTz();

    $table->index(['asset_id', 'created_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
