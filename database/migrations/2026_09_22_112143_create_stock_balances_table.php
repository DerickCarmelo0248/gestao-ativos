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
    Schema::create('stock_balances', function (Blueprint $table) {
        $table->id();

        $table->foreignId('item_id')
            ->constrained()
            ->restrictOnDelete();

        $table->foreignId('unit_id')
            ->constrained()
            ->restrictOnDelete();

        $table->integer('quantity')->default(0);
        $table->timestamps();

        $table->unique(['item_id', 'unit_id']);
    });

    DB::statement(
        'ALTER TABLE stock_balances
         ADD CONSTRAINT stock_balances_quantity_nonnegative
         CHECK (quantity >= 0)'
    );
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
