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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Datos base
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            
            // Detalles extra (Los que iban en la otra migración)
            $table->string('priority')->default('High');
            $table->string('status')->default('Pending');
            $table->string('attachment')->nullable();
            
            // Tiempos y Borrado Lógico
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
