<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;

// 1. LA ENTRADA: Siempre empezamos en el Login
Route::get('/', function () {
    return view('login');
})->name('login');

Route::get('/signup', [RegisterController::class, 'create'])->name('signup.form');

Route::post('/signup', [RegisterController::class, 'store'])->name('signup.store');


// Acción: Al darle clic a "Enviar" en el Login, te lleva al Dashboard
Route::post('/login', function () {
    return redirect('/dashboard'); 
})->name('login.post');

// 2. EL REGISTRO: Pantalla para crear cuenta
Route::get('/registro', function () {
    return view('registro');
})->name('registro');

// Acción: Al darle clic a "Guardar" en el Registro, te regresa al Login
Route::post('/registro', function () {
    return redirect('/'); 
})->name('registro.post');

// 3. EL DASHBOARD: Tu panel principal de tareas
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard') // Importante: así puedes usar route('dashboard')
    ->middleware('auth'); // Protege la ruta

// 4. EL LOGOUT: Botón de salida
Route::post('/logout', function () {
    // Asegúrate de que diga 'logout' entre comillas y sin puntos extra
    return view('logout'); 
})->name('logout');

// Ruta para logout usando tu controlador personalizado
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth'); // Asegurar que solo usuarios autenticados puedan hacer logout