<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement('ALTER TABLE assets DROP CONSTRAINT assets_status_check');
        DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_status_check CHECK (status IN ('available','in_use','awaiting_disposal','in_container','disposed','maintenance'))");
        Schema::create('equipment_maintenances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('patrimony',50);
            $t->string('model_name',255);
            $t->string('previous_status',30)->nullable();
            $t->string('repair_location',255);
            $t->string('ticket_number',100);
            $t->string('sector_name',255);
            $t->string('unit_name',255);
            $t->string('responsible_name',255);
            $t->text('reason');
            $t->boolean('invoice_required')->default(false);
            $t->foreignId('sent_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('sent_at');
            $t->foreignId('returned_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('returned_at')->nullable();
            $t->text('return_notes')->nullable();
        });
        DB::statement("CREATE UNIQUE INDEX maintenance_open_patrimony ON equipment_maintenances (lower(patrimony)) WHERE returned_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX maintenance_open_asset ON equipment_maintenances (asset_id) WHERE returned_at IS NULL AND asset_id IS NOT NULL");
    }
    public function down(): void {
        // Refuse rollback while equipment still depends on this status.
        if (DB::table('assets')->where('status', 'maintenance')->exists()) {
            throw new RuntimeException('Existem equipamentos em manutenção.');
        }
        Schema::dropIfExists('equipment_maintenances');
        DB::statement('ALTER TABLE assets DROP CONSTRAINT assets_status_check');
        DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_status_check CHECK (status IN ('available','in_use','awaiting_disposal','in_container','disposed'))");
    }
};
