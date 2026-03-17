<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Iniciar Sesión</title>
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="card-left brand-bg login-bg">
                <div class="brand-content">
                    <h1>Bienvenido<br>de nuevo.</h1>
                    <p>Accede a tu panel de control y retoma el progreso de tus tareas y proyectos justo donde las dejaste.</p>
                </div>
            </div>

            <div class="card-right">
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">¿Nuevo en TaskFlow? <a href="{{ route('registro') }}">Crea una cuenta</a>.</p>
                
                {{-- Alertas de Laravel (Errores y Éxitos) --}}
                @error('email')
                    <div class="error-msg global-error" style="margin-bottom: 15px;">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                @enderror

                @if(session('success'))
                    <div class="error-msg global-error" style="background-color: #ecfdf5; color: #059669; border-color: #a7f3d0; margin-bottom: 15px;">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="auth-form">
                    @csrf
                    
                    <div class="input-group">
                        <input type="email" name="email" id="usuario" required placeholder="Correo electrónico">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="password" id="password" required placeholder="Contraseña">
                    </div>

                    <div class="form-options">
                        <div class="checkbox-group no-margin">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Recordarme</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-100" style="margin-top: 20px;">Acceder al Dashboard</button>
                </form>
                
            </div>

        </div>
    </div>
</body>
</html>