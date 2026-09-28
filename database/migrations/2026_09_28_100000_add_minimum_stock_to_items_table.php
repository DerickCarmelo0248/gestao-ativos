<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->integer('minimum_stock')->default(0);
        });
        DB::statement('ALTER TABLE items ADD CONSTRAINT items_minimum_stock_nonnegative CHECK (minimum_stock >= 0)');
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('minimum_stock');
        });
    }
};
