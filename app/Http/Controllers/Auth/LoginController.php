<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
     use AuthenticatesUsers;

    /**
     * 
     * @var string
     */
    protected $redirectTo = '/dashboard'; // Tu panel principal

    public function logout(Request $request)
    {
        // 1️⃣ Registrar el logout (opcional pero recomendado para auditoría)
        $user = Auth::user();
        if ($user) {
            // Puedes guardar en logs o en una tabla de auditoría
            \Log::info('Usuario cerró sesión', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        // 2️⃣ Invalidar todos los tokens de sesión (especialmente importante para API)
        if (method_exists($user, 'tokens')) {
            // Si usas Laravel Sanctum o Passport
            $user->tokens()->delete();
        }

        // 3️⃣ Cerrar sesión del usuario (esto invalida la sesión actual)
        Auth::logout();

        // 4️⃣ Invalidar la sesión actual en el servidor
        $request->session()->invalidate();

        // 5️⃣ Regenerar el token CSRF para prevenir ataques de fijación de sesión
        $request->session()->regenerateToken();

        // 6️⃣ Mensaje flash opcional
        Session::flash('success', 'Has cerrado sesión exitosamente.');

        // 7️⃣ Redireccionar al login
        return redirect('/login');
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
