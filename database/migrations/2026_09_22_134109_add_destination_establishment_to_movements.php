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
    foreach (['stock_movements', 'asset_movements'] as $tableName) {
        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('destination_establishment_id')
                ->nullable()
                ->constrained('establishments')
                ->restrictOnDelete();
        });
    }
}

public function down(): void
{
    foreach (['stock_movements', 'asset_movements'] as $tableName) {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['destination_establishment_id']);
            $table->dropColumn('destination_establishment_id');
        });
    }
}
};
