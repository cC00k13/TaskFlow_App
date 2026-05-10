<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Iniciar Sesión</title>
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
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

                <form action="{{ route('login.post') }}" method="POST" class="auth-form" id="form-login">
                    @csrf
                    
                    <div class="input-group">
                        <input type="email" name="email" id="email" placeholder="Correo electrónico" value="{{ old('email') }}">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="password" id="password" placeholder="Contraseña">
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

    {{-- ==========================================
         LÓGICA DE ALERTAS MODERNAS ANIMADAS
         ========================================== --}}
    <script>
        // Configuración Global del Toast Animado
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000, // Un poco más de tiempo para leerlo
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__slideInRight animate__faster' // Entra deslizando por la derecha
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'    // Se va desvaneciendo hacia arriba
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            
            // 1. Mostrar notificación de éxito animada
            @if(session('success'))
                Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
            @endif

            // Fondo oscuro azulado elegante para que la alerta resalte más
            const fondoOscuro = 'rgba(15, 23, 42, 0.6)';

            // 2. Mostrar alerta de error desde Laravel (Credenciales incorrectas)
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Acceso denegado',
                    text: '{{ $errors->first() }}', 
                    confirmButtonColor: '#2563eb', 
                    confirmButtonText: 'Reintentar',
                    backdrop: fondoOscuro,
                    showClass: {
                        popup: 'animate__animated animate__headShake animate__faster' // Efecto de sacudida
                    }
                });
            @endif

            const formLogin = document.getElementById('form-login');
            const inputEmail = document.getElementById('email');
            const inputPassword = document.getElementById('password');

            if(formLogin) {
                formLogin.addEventListener('submit', function(e) {
                    
                    // 3. Validación frontend: Evitar que envíen campos en blanco
                    if (!inputEmail.value.trim() || !inputPassword.value.trim()) {
                        e.preventDefault(); 
                        
                        Swal.fire({
                            title: 'Faltan datos',
                            text: 'Por favor, ingresa tu correo y contraseña para acceder.',
                            icon: 'warning',
                            confirmButtonColor: '#2563eb',
                            confirmButtonText: 'Entendido',
                            backdrop: fondoOscuro,
                            showClass: {
                                popup: 'animate__animated animate__headShake animate__faster' // Efecto de sacudida
                            }
                        });
                        return; 
                    }

                    // 4. Si todo está lleno, mostrar estado de carga (Anti-spam)
                    const btn = this.querySelector('button[type="submit"]');
                    if(btn) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validando credenciales...';
                        btn.style.pointerEvents = 'none';
                        btn.style.opacity = '0.7';
                    }
                });
            }
        });
    </script>
</body>
</html>