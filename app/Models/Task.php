<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'descripcion', 'estado', 'fecha_entrega', 'user_id'];

    protected $casts = [
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->isDirty('estado')) {
                $task->completed_at = $task->status === 'completado' 
                    ? now() 
                    : null;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeChronological($query)
    {
        return $query->latest('fecha_creacion');
    }

    public function scopeOldestFirst($query)
    {
        return $query->oldest('fecha_creacion');
    }

    public function scopePending($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeCompleted($query)
    {
        return $query->where('estado', 'completado');
    }

    // Accessor for quick status check
    public function getIsCompletedAttribute(): bool
    {
        return $this->estado === 'completado';
    }

    public function tags(): BelongsToMany
    {          
        return $this->belongsToMany(Tag::class);
    }

    
}
