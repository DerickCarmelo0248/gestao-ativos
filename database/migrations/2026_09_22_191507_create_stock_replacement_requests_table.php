<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('stock_replacement_requests', function (Blueprint $table) {
        $table->id();

        $table->foreignId('stock_movement_id')
            ->unique()
            ->constrained('stock_movements')
            ->restrictOnDelete();

        $table->foreignId('destination_establishment_id')
            ->constrained('establishments')
            ->restrictOnDelete();

        $table->foreignId('destination_sector_id')
            ->constrained('sectors')
            ->restrictOnDelete();

        $table->integer('quantity');
        $table->text('reason');

        $table->enum('status', [
            'pending',
            'purchasing',
            'completed',
            'cancelled',
        ])->default('pending');

        $table->timestampsTz();
    });

    DB::statement(
        'ALTER TABLE stock_replacement_requests
         ADD CONSTRAINT stock_replacement_requests_quantity_positive
         CHECK (quantity > 0)'
    );
}

public function down(): void
{
    Schema::dropIfExists('stock_replacement_requests');
}
};
