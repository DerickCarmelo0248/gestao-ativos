<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });
        Schema::create('asset_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->jsonb('before');
            $table->jsonb('after');
            $table->timestampTz('created_at');
            $table->index(['asset_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_edits');
        Schema::table('assets', fn (Blueprint $table) => $table->dropColumn('notes'));
    }
};
