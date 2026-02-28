<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Sesión Cerrada</title>
    <link rel="stylesheet" href="{{ asset('css/logout.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            
            <a href="{{ route('login') }}" class="btn-return">Volver al Inicio de Sesión</a>
        </div>

    </div>
</body>
</html>