<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', fn (Blueprint $table) => $table->boolean('minimum_stock_enabled')->default(true));
    }

    public function down(): void
    {
        Schema::table('items', fn (Blueprint $table) => $table->dropColumn('minimum_stock_enabled'));
    }
};
