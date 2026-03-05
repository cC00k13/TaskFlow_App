<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Mis Tareas</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    @if(session('success'))
    <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px auto; width: 80%; text-align: center; font-weight: bold; border: 1px solid #c3e6cb;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    <div class="dashboard-container">
        <div class="dashboard-card">
            
            <header class="header">
                <div class="brand">
                    <h2>TaskFlow.</h2>
                    {{-- Fecha dinámica en español --}}
                    <p class="date">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}</p>
                </div>
                <div class="user-info">
                    {{-- El nombre ahora se jala directamente de la base de datos --}}
                    <span>Hola, <strong>{{ auth()->user()->name ?? '' }}</strong></span>
                    
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="logout-icon" title="Cerrar sesión" style="background: none; border: none; cursor: pointer;">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </header>

            <section class="controls">
                <div class="search-bar">
                    <input type="text" placeholder="Nueva tarea rápida...">
                    <button class="btn-add" onclick="abrirModal()"><i class="fas fa-plus"></i></button>
                </div>
                
                <div class="filter-bar">
                    <label><i class="fas fa-sort-amount-down"></i> Ordenar por:</label>
                    <select class="select-ordenar">
                        <option>Más recientes</option>
                        <option>Prioridad Alta</option>
                        <option>Pendientes primero</option>
                    </select>
                </div>
            </section>

            <section class="task-container">
                <h3>Mis Tareas</h3>
                <ul class="task-list">
                    {{-- Bucle dinámico para las tareas --}}
                    @forelse($tareas ?? [] as $tarea)
                        <li class="task-item {{ $tarea->estado == 'completada' ? 'completed' : '' }}">
                            <input type="checkbox" class="task-check" {{ $tarea->estado == 'completada' ? 'checked' : '' }}>
                            <div class="content" onclick="abrirModal()">
                                <span class="title">{{ $tarea->titulo }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->prioridad) }}">{{ strtoupper($tarea->prioridad) }}</span>
                                    <span class="tag status-{{ strtolower($tarea->estado) }}">{{ strtoupper($tarea->estado) }}</span>
                                </div>
                            </div>
                            <div class="actions">
                                <button class="edit" onclick="abrirModal()" title="Editar"><i class="fas fa-pen"></i></button>
                                <button class="delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </div>
                        </li>
                    @empty
                        <li class="task-item" style="justify-content: center; color: #888;">
                            <span>No hay tareas disponibles. ¡Empieza creando una nueva!</span>
                        </li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    <div class="modal-overlay" id="task-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Detalles de la Tarea</h2>
                <button class="close-modal" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
            </div>
            <form action="/tareas" method="POST">
                @csrf
                <div class="input-group">
                    <input type="text" name="titulo" required placeholder="TÍTULO DE LA TAREA">
                </div>
                <div class="input-group">
                    <textarea name="descripcion" rows="3" placeholder="Descripción de la tarea..."></textarea>
                </div>
                <div class="modal-row">
                    <div class="input-group half">
                        <label class="select-label">PRIORIDAD</label>
                        <select name="prioridad" class="custom-select">
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta" selected>Alta</option>
                        </select>
                    </div>
                    <div class="input-group half">
                        <label class="select-label">ESTADO</label>
                        <select name="estado" class="custom-select">
                            <option value="pendiente">Pendiente</option>
                            <option value="progreso" selected>En Progreso</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar Tarea</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('task-modal');
        function abrirModal() { modal.style.display = 'flex'; }
        function cerrarModal() { modal.style.display = 'none'; }
    </script>
</body>
</html>