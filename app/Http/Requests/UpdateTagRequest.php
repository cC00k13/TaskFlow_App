<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get tag ID safely - handles both route model binding and manual ID
        $tag = $this->route('tag');
        $tagId = $tag ? $tag->id : $this->route('tagId');

        return [
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('tags')
                    ->where(fn ($query) => $query->where('user_id', request()->user()->id))
                    ->ignore($tagId),
            ],
            'color' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Se requiere un nombre para la etiqueta',
            'name.max' => 'El nombre de la etiqueta no puede ser mayor a 30 caracteres',
            'name.unique' => 'El nombre de la etiqueta ya existe',
            'color.required' => 'Por favor selecciona un color',
            'color.regex' => 'El color debe estar en un formato hexadecimal.',
        ];
    }
}
