<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;


// 1. LA ENTRADA: Siempre empezamos en el Login
Route::get('/', function () {
    return view('login');
})->name('login');

Route::get('/signup', [RegisterController::class, 'create'])->name('signup.form');

Route::post('/signup', [RegisterController::class, 'store'])->name('signup.store');


// Acción: Al darle clic a "Enviar" en el Login, verifica en MySQL
Route::post('/login', function (\Illuminate\Http\Request $request) {
    
    // 1. Validar que el usuario sí escribió algo
    $credenciales = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // 2. Auth::attempt va a la base de datos y compara las contraseñas
    if (\Illuminate\Support\Facades\Auth::attempt($credenciales)) {
        // Si todo coincide, le damos el "gafete" de sesión
        $request->session()->regenerate();
        
        // Lo dejamos pasar al Dashboard CON un mensaje de éxito
        return redirect()->intended('/dashboard')->with('success', '¡Inicio de sesión exitoso! Bienvenido de nuevo.');
    }

    // 3. Si se equivoca de contraseña, lo regresamos con un error
    return back()->withErrors([
        'email' => 'Las credenciales no coinciden con nuestros registros.',
    ]);

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
        'email'    => 'required|email|unique:users,email', 
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

    // 3. Si todo sale bien, te regresa al login y manda un mensaje temporal (flash)
    return redirect('/')->with('success', '¡Registro exitoso! Ya puedes iniciar sesión con tu cuenta.');
})->name('registro.post');

// 3. EL DASHBOARD: Tu panel principal de tareas
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard') // Importante: así puedes usar route('dashboard')
    ->middleware('auth'); // Protege la ruta

// 4. EL LOGOUT: Acción para cerrar la puerta con llave
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    
    // 1. Rescatamos el nombre del usuario ANTES de destruir sus datos
    $nombre = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'Usuario';
    
    // 2. Cerramos la sesión activa en el servidor
    \Illuminate\Support\Facades\Auth::logout();
    
    // 3. Invalidamos la sesión y regeneramos el token (Seguridad total)
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    // 4. Lo redirigimos a la pantalla visual, enviando el nombre temporalmente
    return redirect('/despedida')->with('nombre_usuario', $nombre); 
})->name('logout');

// 5. PANTALLA DE DESPEDIDA: Muestra tu vista personalizada
Route::get('/despedida', function () {
    return view('logout');
})->name('despedida');