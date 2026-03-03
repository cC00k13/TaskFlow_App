<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
     /**
     * Muestra el formulatio de inicio
     */
    public function create(): View
    {
        return view('auth.registro');
    }

    /**
     * Maneja formulario de inicio de sesion
     */
    public function store(Request $request): RedirectResponse
    {
        // Valida los datos entrates con mensajes personalizados
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contrasena' => 'required|min:8|confirmed',
        ], [
            // Mensajes de error personalizados
            'nombre.required' => 'Por favor, ingresa tu nombre.',
            'nombre.max' => 'El nombre es muy largo, intenta 255 caracteres',
            'email.required' => 'Se requiere direcciond de correo electronico.',
            'email.email' => 'Por far ingresa una direccion de correo valida.',
            'email.unique' => 'Esta direccion de correo ya esta registrada',
            'contrasena.required' => 'Se requiere contrasena',
            'contrasena.min' => 'La contrasena debe tener por los menos 8 caracteres.',
            'contrasena.confirmed' => 'La confirmacion de contrasena no funciona',
        ]);

        // Crea al usuario
        $user = User::create([
            'nombre' => $validatedData['nombre'],
            'email' => $validatedData['email'],
            'contrasena' => Hash::make($validatedData['contrasena']),
        ]);

        // Muestra un mensaje flash de exito
        return redirect()->route('signup.form')
                         ->with('exito', 'Registro exitoso!');
    }
}
