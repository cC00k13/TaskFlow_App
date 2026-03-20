<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Importamos la clase de borrado lógico

class Task extends Model
{
    // 2. Activamos el SoftDeletes junto con HasFactory
    use HasFactory, SoftDeletes; 

    // 3. Permitimos que estos campos se llenen masivamente desde tu formulario
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'attachment',
    ];

    // Relación: Una tarea pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Una tarea puede tener múltiples etiquetas (Muchos a Muchos)
    public function labels()
    {
        return $this->belongsToMany(Label::class);
    }
}