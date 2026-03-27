<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación: las tags pertenecen al usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relacion: los tags pertenecen a muchas tareas
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    // Funcion auxiliar para color de css
    public function getColorStyleAttribute(): string
    {
        return "background-color: {$this->color}; color: " . $this->getContrastColor($this->color);
    }

    // Funcion auxiliar para detectar el texto con el contraste del color
    private function getContrastColor(string $hexColor): string
    {
        $hex = ltrim($hexColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }
}
