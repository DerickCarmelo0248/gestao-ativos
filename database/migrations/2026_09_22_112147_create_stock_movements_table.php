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
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();

        $table->foreignId('item_id')
            ->constrained()
            ->restrictOnDelete();

        $table->foreignId('unit_id')
            ->constrained()
            ->restrictOnDelete();

        $table->foreignId('user_id')
            ->constrained()
            ->restrictOnDelete();

        $table->string('type', 30);
        $table->integer('quantity');
        $table->text('notes')->nullable();
        $table->timestampsTz();

        $table->index(['item_id', 'unit_id', 'created_at']);
    });

    DB::statement(
        'ALTER TABLE stock_movements
         ADD CONSTRAINT stock_movements_quantity_positive
         CHECK (quantity > 0)'
    );
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
