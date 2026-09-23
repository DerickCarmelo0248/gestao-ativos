<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_container_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('disposal_container_id')
                ->constrained('disposal_containers')
                ->restrictOnDelete();

            $table->foreignId('asset_id')
                ->nullable()
                ->unique()
                ->constrained('assets')
                ->restrictOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();

            $table->foreignId('origin_unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->foreignId('added_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->integer('quantity')->default(1);

            // Dados preservados para os relatórios.
            $table->string('category_name', 100);
            $table->string('item_code', 50);
            $table->string('item_name', 150);
            $table->string('origin_unit_name', 100);
            $table->string('patrimony', 50)->nullable();
            $table->string('serial_number', 100)->nullable();

            $table->text('reason');
            $table->timestampTz('added_at');
            $table->timestampsTz();

            $table->index(['disposal_container_id', 'item_id']);
        });

        DB::statement("
            ALTER TABLE disposal_container_items
            ADD CONSTRAINT disposal_container_items_quantity_check
            CHECK (
                quantity > 0
                AND (
                    asset_id IS NULL
                    OR (quantity = 1 AND patrimony IS NOT NULL)
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_container_items');
    }
};