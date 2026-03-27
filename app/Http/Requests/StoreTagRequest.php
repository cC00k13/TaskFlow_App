<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('tags')->where(function ($query) {
                    return $query->where('user_id', request()->user()?->id);
                }),
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
            'name.required' => 'The tag name is required.',
            'name.max' => 'The tag name cannot exceed 30 characters.',
            'name.unique' => 'That tag name is already in use.',
            'color.required' => 'Please select a color.',
            'color.regex' => 'The color must be a valid hexadecimal format (e.g., #FF5733).',
        ];
    }
}
