<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_deletions', function (Blueprint $table) {
            $table->id();
            // Intentionally not a foreign key: this identity survives deletion.
            $table->unsignedBigInteger('original_asset_id')->index();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->jsonb('snapshot');
            $table->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_deletions');
    }
};