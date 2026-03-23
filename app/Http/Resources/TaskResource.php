<?php
// app/Http/Resources/TaskResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'se_completo' => $this->se_completo, // accessor
            'fecha_completado' => $this->fecha_completado?->diffForHumans(), // null-safe operator
            'fecha_creacion' => $this->fecha_creacion->toISOString(),
            'fecha_actualizacion' => $this->fecha_actualizacion->toISOString(),
        ];
    }
}