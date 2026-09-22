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
        Schema::create('assets', function (Blueprint $table) {
    $table->id();

    $table->foreignId('item_id')
        ->constrained()
        ->restrictOnDelete();

    $table->foreignId('unit_id')
        ->constrained()
        ->restrictOnDelete();

    $table->string('patrimony', 50)->unique();
    $table->string('serial_number', 100)->nullable();

    $table->enum('status', [
        'available',
        'in_use',
        'awaiting_disposal',
        'in_container',
        'disposed',
    ])->default('available');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
