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
    Schema::table('stock_replacement_requests', function (Blueprint $table) {
        $table->foreignId('replacement_movement_id')
            ->nullable()
            ->unique()
            ->constrained('stock_movements')
            ->restrictOnDelete();

        $table->foreignId('completed_by')
            ->nullable()
            ->constrained('users')
            ->restrictOnDelete();

        $table->timestampTz('completed_at')->nullable();
    });
}

public function down(): void
{
    Schema::table('stock_replacement_requests', function (Blueprint $table) {
        $table->dropForeign(['replacement_movement_id']);
        $table->dropForeign(['completed_by']);

        $table->dropUnique(['replacement_movement_id']);

        $table->dropColumn([
            'replacement_movement_id',
            'completed_by',
            'completed_at',
        ]);
    });
}
};
