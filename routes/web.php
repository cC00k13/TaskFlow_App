<?php


use Illuminate\Support\Facades\Route;

// 1. LA ENTRADA: Siempre empezamos en el Login
Route::get('/', function () {
    return view('login');
})->name('login');

// Acción: Al darle clic a "Enviar" en el Login, te lleva al Dashboard
Route::post('/login', function () {
    return redirect('/dashboard'); 
})->name('login.post');

// 2. EL REGISTRO: Pantalla para crear cuenta
Route::get('/registro', function () {
    return view('registro');
})->name('registro');

// Acción: Al darle clic a "Guardar" en el Registro
Route::post('/registro', function (\Illuminate\Http\Request $request) {
    
    // 1. LA TAREA: Validar que el correo sea único en la tabla 'users'
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email', // ¡Aquí está la magia de tu tarea!
        'password' => 'required'
    ], [
        // Mensaje de error personalizado para cuando se repita el correo
        'email.unique' => '¡Error! Este correo ya existe en nuestra base de datos.'
    ]);

    // 2. Guardar en la base de datos (Necesario para que la validación tenga contra qué comparar)
    \App\Models\User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
    ]);

    // 3. Si todo sale bien, te regresa al login
    return redirect('/'); 
})->name('registro.post');

// 3. EL DASHBOARD: Tu panel principal de tareas
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// 4. EL LOGOUT: Botón de salida
Route::post('/logout', function () {
    // Asegúrate de que diga 'logout' entre comillas y sin puntos extra
    return view('logout'); 
})->name('logout');