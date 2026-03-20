<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TaskController;

// 1. LA ENTRADA: Siempre empezamos en el Login
Route::get('/', function () {
    return view('login');
})->name('login');

Route::get('/signup', [RegisterController::class, 'create'])->name('signup.form');

Route::post('/signup', [RegisterController::class, 'store'])->name('signup.store');


Route::post('/login', function (\Illuminate\Http\Request $request) {
    
    $credenciales = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ], [
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.email'    => 'Por favor, ingresa un formato de correo válido (ejemplo@correo.com).',
        'password.required' => 'La contraseña es obligatoria.'
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credenciales)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard')->with('success', '¡Inicio de sesión exitoso! Bienvenido de nuevo.');
    }

    return back()->withErrors([
        'email' => 'Las credenciales no coinciden con nuestros registros.',
    ]);

})->name('login.post');

// 2. EL REGISTRO: Pantalla para crear cuenta (¡Esta es la ruta que faltaba!)
Route::get('/registro', function () {
    return view('registro');
})->name('registro');

// Acción: Al darle clic a "Guardar" en el Registro
Route::post('/registro', function (\Illuminate\Http\Request $request) {
    
    // Validar que el correo sea único
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required'
    ], [
        'email.unique' => '¡Error! Este correo ya existe en nuestra base de datos.'
    ]);

    // Guardar en la base de datos
    \App\Models\User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
    ]);

    // Redirigir con mensaje de éxito
    return redirect('/')->with('success', '¡Registro exitoso! Ya puedes iniciar sesión.'); 
})->name('registro.post');

// 3. EL DASHBOARD: Tu panel principal de tareas
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard') // Importante: así puedes usar route('dashboard')
    ->middleware('auth'); // Protege la ruta

// Ruta para logout usando tu controlador personalizado
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth'); // Asegurar que solo usuarios autenticados puedan hacer logout


//Ruta para el controlador de validación de creación de tarea
Route::get('/task/create', [TaskController::class, 'create']);
Route::post('/task/create', [TaskController::class, 'store']);

// 5. PANTALLA DE DESPEDIDA: Muestra la vista de logout.blade.php
Route::get('/despedida', function () {
    return view('logout');
})->name('despedida');