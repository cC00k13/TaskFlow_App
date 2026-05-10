<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Registro</title>
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
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
                
                <form action="{{ route('registro.post') }}" method="POST" class="auth-form" id="form-registro">
                    @csrf
                    
                    <div class="input-group">
                        <input type="text" id="nombre" name="name" placeholder="Nombre completo" value="{{ old('name') }}">
                    </div>
                    
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Correo electrónico" value="{{ old('email') }}">
                    </div>

                    <div class="input-group">
                        <input type="tel" id="telefono" name="phone" placeholder="Teléfono (Opcional)" value="{{ old('phone') }}">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="Contraseña">
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="checkbox-terminos" name="terms">
                        <label for="checkbox-terminos">Acepto los términos, condiciones y política de privacidad</label>
                    </div>
                    
                    <button type="submit" class="btn-primary w-100">Registrarse</button>
                </form>
            </div>

        </div>
    </div>

    {{-- ==========================================
         LÓGICA DE ALERTAS MODERNAS Y ANTI-SPAM
         ========================================== --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // Fondo oscuro azulado elegante
            const fondoOscuro = 'rgba(15, 23, 42, 0.6)';

            // 1. Mostrar alerta de error desde el Backend (Ej. Correo duplicado)
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'No pudimos registrarte',
                    text: '{{ $errors->first() }}', 
                    confirmButtonColor: '#2563eb', 
                    confirmButtonText: 'Corregir',
                    backdrop: fondoOscuro,
                    showClass: {
                        popup: 'animate__animated animate__headShake animate__faster' // Efecto sacudida
                    }
                });
            @endif

            const formRegistro = document.getElementById('form-registro');
            const inputNombre = document.getElementById('nombre');
            const inputEmail = document.getElementById('email');
            const inputPassword = document.getElementById('password');
            const checkboxTerminos = document.getElementById('checkbox-terminos');

            if(formRegistro) {
                formRegistro.addEventListener('submit', function(e) {
                    
                    // 2. Validar que los campos de texto no estén vacíos
                    if (!inputNombre.value.trim() || !inputEmail.value.trim() || !inputPassword.value.trim()) {
                        e.preventDefault(); // Detiene el envío
                        
                        Swal.fire({
                            title: 'Faltan datos',
                            text: 'Por favor, completa tu Nombre, Correo y Contraseña para continuar.',
                            icon: 'warning',
                            confirmButtonColor: '#2563eb',
                            confirmButtonText: 'Entendido',
                            backdrop: fondoOscuro,
                            showClass: {
                                popup: 'animate__animated animate__headShake animate__faster'
                            }
                        });
                        return; // Evita que el código siga avanzando
                    }

                    // 3. Validar que la casilla de términos esté marcada
                    if (!checkboxTerminos.checked) {
                        e.preventDefault(); 
                        
                        Swal.fire({
                            title: 'Términos y Condiciones',
                            text: 'Debes aceptar la política de privacidad y los términos para crear tu cuenta.',
                            icon: 'info',
                            confirmButtonColor: '#2563eb',
                            confirmButtonText: 'Entendido',
                            backdrop: fondoOscuro,
                            showClass: {
                                popup: 'animate__animated animate__headShake animate__faster'
                            }
                        });
                        return;
                    } 
                    
                    // 4. Si todo está lleno, Prevención de envíos múltiples (Spam de clics)
                    const btn = this.querySelector('button[type="submit"]');
                    if(btn) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando cuenta...';
                        btn.style.pointerEvents = 'none';
                        btn.style.opacity = '0.7';
                    }
                });
            }
        });
    </script>
</body>
</html>