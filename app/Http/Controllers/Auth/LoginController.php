<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    protected $redirectTo = '/dashboard'; // Tu panel principal

    public function logout(Request $request)
    {
        // 1. Rescatamos el nombre del usuario ANTES de destruir sus datos
        $nombre = Auth::check() ? Auth::user()->name : 'Usuario';

        // 2. Cerramos la sesión y limpiamos todo por dentro (Seguridad)
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Lo redirigimos a la pantalla de despedida, enviándole su nombre
        return redirect('/despedida')->with('nombre_usuario', $nombre);
    }
}