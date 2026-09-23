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
    Schema::create('asset_replacement_requests', function (Blueprint $table) {
        $table->id();

        $table->foreignId('asset_movement_id')
            ->unique()
            ->constrained('asset_movements')
            ->restrictOnDelete();

        $table->foreignId('destination_establishment_id')
            ->constrained('establishments')
            ->restrictOnDelete();

        $table->foreignId('destination_sector_id')
            ->constrained('sectors')
            ->restrictOnDelete();

        $table->enum('status', [
            'pending',
            'purchasing',
            'completed',
            'cancelled',
        ])->default('pending');

        $table->foreignId('replacement_asset_id')
            ->nullable()
            ->unique()
            ->constrained('assets')
            ->restrictOnDelete();

        $table->foreignId('replacement_movement_id')
            ->nullable()
            ->unique()
            ->constrained('asset_movements')
            ->restrictOnDelete();

        $table->foreignId('completed_by')
            ->nullable()
            ->constrained('users')
            ->restrictOnDelete();

        $table->timestampTz('completed_at')->nullable();
        $table->timestampsTz();
    });
}

public function down(): void
{
    Schema::dropIfExists('asset_replacement_requests');
}
};
