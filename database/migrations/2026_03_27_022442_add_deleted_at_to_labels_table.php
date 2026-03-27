<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labels', function (Blueprint $table) {
            // Agrega la columna deleted_at
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('labels', function (Blueprint $table) {
            // Permite dar marcha atrás si nos equivocamos
            $table->dropSoftDeletes();
        });
    }
};