<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    // Cambiamos 'attachment' por 'attachments'
    protected $fillable = [
        'user_id', 
        'title', 
        'description', 
        'due_date', 
        'priority', 
        'status', 
        'attachments' 
    ];

    // Magia de Eloquent: Convierte el JSON de la BD a un Array de PHP automáticamente
    protected $casts = [
        'attachments' => 'array',
    ];

    public function labels()
    {
        return $this->belongsToMany(Label::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}