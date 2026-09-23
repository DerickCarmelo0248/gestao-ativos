<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_container_items', function (Blueprint $table) {
            $table->enum('registration_source', [
                'registered',
                'external',
            ])->default('registered');
        });
    }

    public function down(): void
    {
        Schema::table('disposal_container_items', function (Blueprint $table) {
            $table->dropColumn('registration_source');
        });
    }
};