<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_containers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->enum('status', ['open', 'closed'])
                ->default('open');

            $table->foreignId('opened_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestampTz('opened_at');

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestampTz('closed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->index(['unit_id', 'status']);
        });

        // Permite somente uma caçamba aberta por unidade.
        DB::statement("
            CREATE UNIQUE INDEX disposal_containers_one_open_per_unit
            ON disposal_containers (unit_id)
            WHERE status = 'open'
        ");

        // Mantém os dados de encerramento coerentes com a situação.
        DB::statement("
            ALTER TABLE disposal_containers
            ADD CONSTRAINT disposal_containers_closure_check
            CHECK (
                (
                    status = 'open'
                    AND closed_by IS NULL
                    AND closed_at IS NULL
                )
                OR
                (
                    status = 'closed'
                    AND closed_by IS NOT NULL
                    AND closed_at IS NOT NULL
                    AND closed_at >= opened_at
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_containers');
    }
};