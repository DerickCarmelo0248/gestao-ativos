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
        Schema::create('items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('category_id')
        ->constrained()
        ->restrictOnDelete();

    $table->string('code', 50)->unique();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->enum('tracking_type', ['quantity', 'individual']);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
