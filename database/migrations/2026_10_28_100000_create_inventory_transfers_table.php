<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('from_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_transfers'); }
};
