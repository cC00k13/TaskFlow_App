<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Registro</title>
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="card-left brand-bg">
                <div class="brand-content">
                    <h1>Comienza<br>con TaskFlow.</h1>
                    <p>Crea una cuenta para empezar a gestionar tus tareas y mantener todos los proyectos de tu equipo organizados de manera eficiente.</p>
                </div>
            </div>

            <div class="card-right">
                <h2>Registro</h2>
                <p class="subtitle">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión aquí</a>.</p>
                
                <form action="{{ route('registro.post') }}" method="POST" class="auth-form">
                    @csrf
                    
                    <div class="input-group">
                        <input type="text" id="nombre" name="name" required placeholder="Nombre completo">
                    </div>
                    
                    <div class="input-group">
                        <input type="email" id="email" name="email" required placeholder="Correo electrónico">
                        @error('email')
                            <div class="error-msg">
                                 <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="input-group">
                        <input type="tel" id="telefono" name="phone" placeholder="Teléfono (Opcional)">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder="Contraseña">
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">Acepto los términos, condiciones y política de privacidad</label>
                    </div>
                    
                    <button type="submit" class="btn-primary w-100">Registrarse</button>
                </form>

                <div class="divider">
                    <span>Registro con redes sociales</span>
                </div>

                <div class="social-buttons">
                    <button type="button" class="btn-social google">
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button type="button" class="btn-social facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    <button type="button" class="btn-social twitter">
                        <i class="fab fa-twitter"></i> Twitter
                    </button>
                </div>
            </div>

        </div>
    </div>
</body>
</html>