<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Login</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            
            <div class="card-left">
                <h1>Welcome to<br>TaskFlow.</h1>
                <p>Inicia sesión para empezar a gestionar tus tareas y mantener todos tus proyectos organizados de manera eficiente.</p>
                
                <div class="social-login">
                    <p>Login con redes sociales</p>
                    <div class="social-buttons">
                        <button type="button" class="btn-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button type="button" class="btn-twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-right">
                <h2>Login</h2>
                
                @error('email')
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-weight: bold; border: 1px solid #f5c6cb;">
                        {{ $message }}
                    </div>
                @enderror

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    @if(session('success'))
                        <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-weight: bold;">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <div class="input-group">
                        <input type="text" name="email" id="usuario" required placeholder="Usuario o Correo">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="password" id="password" required placeholder="Password">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Enviar</button>
                    </div>
                </form>

                <div class="register-link">
                    <p>¿No tienes cuenta? <a href="{{ route('registro') }}">Regístrate</a></p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>