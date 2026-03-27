<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\LabelController;
use App\Models\User;
use App\Models\Label;

/* =========================================
   1. AUTENTICACIÓN (Login y Registro)
   ========================================= */

// Mostrar vista de Login
Route::get('/', function () {
    return view('login');
})->name('login');

// Procesar el Login
Route::post('/login', function (Request $request) {
    $credenciales = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ], [
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.email'    => 'Por favor, ingresa un formato de correo válido (ejemplo@correo.com).',
        'password.required' => 'La contraseña es obligatoria.'
    ]);

    if (Auth::attempt($credenciales)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard')->with('success', '¡Inicio de sesión exitoso! Bienvenido de nuevo.');
    }

    return back()->withErrors([
        'email' => 'Las credenciales no coinciden con nuestros registros.',
    ]);
})->name('login.post');

// Mostrar vista de Registro
Route::get('/registro', function () {
    return view('registro');
})->name('registro');

// Procesar el Registro
Route::post('/registro', function (Request $request) {
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required'
    ], [
        'email.unique' => '¡Error! Este correo ya existe en nuestra base de datos.'
    ]);

    User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return redirect('/')->with('success', '¡Registro exitoso! Ya puedes iniciar sesión.'); 
})->name('registro.post');

// Procesar Logout y Vista de Despedida
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/despedida', function () {
    return view('logout');
})->name('despedida');


/* =========================================
   2. DASHBOARD (Panel Principal)
   ========================================= */
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');


/* =========================================
   3. TAREAS (CRUD y Estados)
   ========================================= */
Route::middleware('auth')->group(function () {
    
    // Crear Tarea
    Route::get('/task/create', [TaskController::class, 'create']);
    Route::post('/task/create', [TaskController::class, 'store']);
    
    // Actualizar Tarea Completa (Editar)
    Route::put('/task/{id}', [TaskController::class, 'update'])->name('task.update');
    
    // Eliminar Tarea
    Route::delete('/task/{id}', [TaskController::class, 'destroy'])->name('task.destroy');

    // Cambiar Estado (Pendiente <-> Completada)
    Route::patch('/tareas/{id}/estado', [TaskController::class, 'toggleStatus'])->name('task.toggleStatus');

    // Cambiar Estado vía AJAX (Drag and Drop) - Movido a su lugar correcto
    Route::patch('/tareas/{id}/estado-ajax', [TaskController::class, 'updateStatusAjax']);
});


/* =========================================
   4. ETIQUETAS (Labels)
   ========================================= */
Route::middleware('auth')->group(function () {
    // CRUD completo apuntando al LabelController
    Route::post('/label/create', [LabelController::class, 'store'])->name('labels.store');
    Route::put('/labels/{id}', [LabelController::class, 'update'])->name('labels.update');
    Route::delete('/labels/{id}', [LabelController::class, 'destroy'])->name('labels.destroy');
});