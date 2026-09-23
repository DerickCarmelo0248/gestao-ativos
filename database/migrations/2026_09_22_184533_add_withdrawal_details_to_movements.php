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
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('technicians')
                ->restrictOnDelete();

            $table->string('ticket_number', 100)->nullable();

            $table->boolean('replacement_required')
                ->default(false);
        });
    }
}

public function down(): void
{
    foreach (['stock_movements', 'asset_movements'] as $tableName) {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['technician_id']);

            $table->dropColumn([
                'technician_id',
                'ticket_number',
                'replacement_required',
            ]);
        });
    }
}
};
