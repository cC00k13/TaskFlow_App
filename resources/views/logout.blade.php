<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Sesión Cerrada</title>
    <link rel="stylesheet" href="{{ asset('css/logout.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="logout-container">
        
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h2>¡Sesión cerrada!</h2>
            
            {{-- Usamos session('nombre') que vendrá desde el controlador de PHP --}}
            <p>Has salido de tu cuenta correctamente. ¡Hasta pronto, <strong>{{ session('nombre_usuario', 'Usuario') }}</strong>! Esperamos verte de vuelta para seguir organizando tus proyectos.</p>
            
            <a href="{{ route('login') }}" class="btn-return" id="btn-volver-login" style="display: inline-block; text-align: center; text-decoration: none;">Volver al Inicio de Sesión</a>
        </div>

    </div>

    {{-- ==========================================
         LÓGICA DE ALERTAS MODERNAS Y TRANSICIONES
         ========================================== --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // 1. Configuración del Toast Unificado
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // 2. Disparar un pequeño mensaje de despedida automático
            Toast.fire({ 
                icon: 'success', 
                title: "Sesión finalizada con éxito" 
            });

            // 3. Efecto de carga en el botón (Simulando la prevención de spam de los otros forms)
            const btnVolver = document.getElementById('btn-volver-login');
            if(btnVolver) {
                btnVolver.addEventListener('click', function(e) {
                    // No detenemos el evento (porque queremos que viaje a la otra página),
                    // pero cambiamos el HTML interno para mostrar la carga.
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
                    this.style.pointerEvents = 'none';
                    this.style.opacity = '0.7';
                });
            }
        });
    </script>
</body>
</html>