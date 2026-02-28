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
    <div class="login-container">
        <div class="login-card register-card">
            
            <div class="card-left">
                <h1>Join<br>TaskFlow.</h1>
                <p>Crea una cuenta para empezar a gestionar tus tareas y mantener todos los proyectos de tu equipo organizados de manera eficiente.</p>
            </div>

            <div class="card-right">
                <h2>Register</h2>
                <p class="subtitle">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión aquí</a>.</p>
                
                <form action="{{ route('registro.post') }}" method="POST">
                    @csrf
                    
                    <div class="input-group">
                        <input type="text" id="nombre" name="name" required placeholder="NAME">
                    </div>
                    
                    <div class="input-group">
                        <input type="email" id="email" name="email" required placeholder="EMAIL ID">
                    </div>

                    <div class="input-group">
                        <input type="tel" id="telefono" name="phone" placeholder="PHONE NO">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder="PASSWORD">
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">Acepto los términos, condiciones y política de privacidad</label>
                    </div>
                    
                    <div class="form-actions right-align">
                        <button type="submit" class="btn-submit">Registrarse</button>
                    </div>
                </form>

                <div class="social-login-bottom">
                    <p>Registro con redes sociales</p>
                    <div class="social-buttons small-buttons">
                        <button type="button" class="btn-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button type="button" class="btn-twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <button type="button" class="btn-google">
                            <i class="fab fa-google"></i> Google+
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>