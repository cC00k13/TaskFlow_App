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
    
    {{-- Notificación de Éxito --}}
    @if(session('success'))
    <div id="toast-exito" style="position: fixed; top: 20px; right: 20px; background-color: #ffffff; color: #155724; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-weight: 500; border-left: 5px solid #28a745; z-index: 9999; transition: opacity 0.5s ease; display: flex; align-items: center; gap: 10px; font-family: sans-serif;">
        <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.2rem;"></i> 
        {{ session('success') }}
    </div>

    <script>
        setTimeout(function() {
            let toast = document.getElementById('toast-exito');
            if (toast) {
                toast.style.opacity = '0';
                setTimeout(function() { 
                    toast.remove(); 
                }, 500); 
            }
        }, 3500);
    </script>
    @endif

    <div class="dashboard-container">
        <div class="dashboard-card">
            
            <header class="header">
                <div class="brand">
                    <h2>TaskFlow.</h2>
                    <p class="date">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}</p>
                </div>
                <div class="user-info">
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
                    @forelse($tareas ?? [] as $tarea)
                        <li class="task-item {{ $tarea->estado == 'completada' ? 'completed' : '' }}">
                            <input type="checkbox" class="task-check" {{ $tarea->estado == 'completada' ? 'checked' : '' }}>
                            <div class="content" onclick="abrirModal()">
                                <span class="title">{{ $tarea->titulo }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->prioridad) }}">{{ strtoupper($tarea->prioridad) }}</span>
                                    <span class="tag status-{{ strtolower($tarea->estado) }}">{{ strtoupper($tarea->estado) }}</span>
                                </div>
                                {{-- Metadatos: Fechas y Adjuntos visibles en la lista --}}
                                <div class="task-meta" style="font-size: 0.75rem; color: #888; margin-top: 8px; display: flex; gap: 15px;">
                                    <span><i class="far fa-calendar-alt"></i> Creada: {{ $tarea->fecha_asignacion ?? date('Y-m-d') }}</span>
                                    @if(!empty($tarea->fecha_limite))
                                        <span style="color: #d32f2f;"><i class="far fa-clock"></i> Límite: {{ $tarea->fecha_limite }}</span>
                                    @endif
                                    @if(!empty($tarea->documento))
                                        <span><i class="fas fa-paperclip"></i> Adjunto</span>
                                    @endif
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
            
            {{-- El enctype es obligatorio para adjuntar archivos --}}
            <form action="/tareas" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <input type="text" name="titulo" required placeholder="TÍTULO DE LA TAREA">
                </div>
                <div class="input-group">
                    <textarea name="descripcion" rows="3" placeholder="Descripción de la tarea..."></textarea>
                </div>
                
                {{-- Fila de Fechas --}}
                <div class="modal-row">
                    <div class="input-group half">
                        <label class="select-label">FECHA DE ASIGNACIÓN</label>
                        <input type="date" name="fecha_asignacion" value="{{ date('Y-m-d') }}" readonly style="color: #888; cursor: not-allowed; background-color: #fafafa; border-bottom: 1px dashed #ddd;" class="custom-select">
                    </div>
                    <div class="input-group half">
                        <label class="select-label">FECHA LÍMITE</label>
                        <input type="date" name="fecha_limite" class="custom-select">
                    </div>
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

                {{-- Campo para Documentos --}}
                <div class="input-group" style="margin-top: 10px;">
                    <label class="select-label"><i class="fas fa-paperclip"></i> ADJUNTAR DOCUMENTO</label>
                    <input type="file" name="documento" class="custom-select" accept=".pdf,.doc,.docx,.jpg,.png" style="padding-top: 15px;">
                </div>

                <div class="form-actions" style="margin-top: 25px;">
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