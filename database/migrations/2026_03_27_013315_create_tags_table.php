<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 30);
            $table->string('color', 7)->default('#3B82F6'); // Hex color
            $table->timestamps();
            
            // Ensure unique tag names per user
            $table->unique(['user_id', 'name']);
        });
        
        // Pivot table for tasks and tags
        Schema::create('tag_task', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->primary(['tag_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_task');
        Schema::dropIfExists('tags');
    }
};
