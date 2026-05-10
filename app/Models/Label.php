<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- 1. Importamos la clase

class Label extends Model
{
    use HasFactory, SoftDeletes; // <-- 2. Activamos el SoftDeletes aquí

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];


    // Relación: Una etiqueta pertenece al usuario que la creó
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Una etiqueta puede estar asignada a múltiples tareas
    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }
}