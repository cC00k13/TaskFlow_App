<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'descripcion', 'fecha_entrega', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeChronological($query)
    {
        return $query->latest('created_at');
    }

    public function scopeOldestFirst($query)
    {
        return $query->oldest('created_at');
    }
}
