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
    <div id="toast-exito" class="toast-notification">
        <i class="fas fa-check-circle"></i> 
        <span>{{ session('success') }}</span>
    </div>
    <script>setTimeout(() => { let t = document.getElementById('toast-exito'); if(t) { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); } }, 3500);</script>
    @endif

    {{-- Lógica para separar y contar tareas --}}
    @php
        $listaTareas = collect($tasks ?? []);
        $pendientes = $listaTareas->where('estado', '!=', 'completada');
        $completadas = $listaTareas->where('estado', 'completada');
    @endphp

    <main class="dashboard-container">
        <div class="dashboard-card">
            
            {{-- Encabezado --}}
            <header class="header">
                <div class="brand">
                    <h2>TaskFlow.</h2>
                    <p class="date">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}</p>
                </div>
                <div class="user-info">
                    <span class="greeting">Hola, <strong>{{ auth()->user()->name ?? 'Usuario' }}</strong></span>
                    <button class="btn-outline" onclick="abrirModalEtiquetas()">
                        <i class="fas fa-tags"></i> Mis Etiquetas
                    </button>
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="logout-icon" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></button>
                    </form>
                </div>
            </header>

            {{-- Barra de Controles --}}
            <section class="controls">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Buscar tareas...">
                    <button class="btn-primary" onclick="abrirModalNuevaTarea()">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                </div>
                
                <div class="filters-and-summary">
                    {{-- AQUÍ ESTÁ EL SELECTOR "ORDENAR POR" RESTAURADO --}}
                    <div class="filter-bar">
                        <label><i class="fas fa-sort-amount-down"></i> Ordenar por:</label>
                        <select class="select-ordenar">
                            <option selected>Más próximas a vencer</option>
                            <option>Más recientes</option>
                            <option>Prioridad más alta</option>
                        </select>
                    </div>
                    
                    <span class="badge-pending">{{ $pendientes->count() }} Tareas Pendientes</span>
                </div>
            </section>

            {{-- Sección: Tareas Pendientes --}}
            <section class="task-section">
                <h3 class="section-title">En Curso</h3>
                <ul class="task-list">
                    @forelse($pendientes as $tarea)
                        <li class="task-item">
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="estado" value="completada">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" title="Marcar como completada">
                            </form>
                            <div class="content" onclick="abrirModalEditar(this)" 
                                data-id="{{ $tarea->id }}" data-titulo="{{ $tarea->title }}" 
                                data-descripcion="{{ $tarea->description }}" data-fecha_limite="{{ $tarea->due_date }}" 
                                data-prioridad="{{ $tarea->priority }}" data-estado="{{ $tarea->status }}"
                                data-etiquetas="{{ json_encode(isset($tarea->labels) ? $tarea->labels->pluck('id') : []) }}">

                                <span class="title">{{ $tarea->title }}</span>

                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->prioridad ?? 'media') }}">{{ strtoupper($tarea->prioridad ?? 'MEDIA') }}</span>
                                    
                                    @foreach($tarea->etiquetas ?? [] as $etiqueta)
                                        <span class="tag tag-custom" style="background-color: {{ $etiqueta->color ?? '#eee' }};">{{ $etiqueta->nombre }}</span>
                                    @endforeach
                                </div>

                                <div class="task-meta">
                                    @if(!empty($tarea->fecha_limite))
                                        <span class="meta-date {{ \Carbon\Carbon::parse($tarea->fecha_limite)->isPast() ? 'overdue' : '' }}">
                                            <i class="far fa-clock"></i> {{ $tarea->fecha_limite }}
                                        </span>
                                    @endif
                                    @if(!empty($tarea->documento))
                                        <span class="meta-file"><i class="fas fa-paperclip"></i> Archivo adjunto</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn-icon edit" onclick="abrirModalEditar(this.parentElement.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                <form action="/tareas/{{ $tarea->id ?? 0 }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea permanentemente?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>¡Todo al día! No tienes tareas pendientes.</p>
                        </div>
                    @endforelse
                </ul>
            </section>

            {{-- Sección: Tareas Completadas --}}
            @if($completadas->count() > 0)
            <section class="task-section mt-4">
                <h3 class="section-title text-muted">Completadas</h3>
                <ul class="task-list">
                    @foreach($completadas as $tarea)
                        <li class="task-item completed">
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="estado" value="pendiente">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" checked title="Desmarcar">
                            </form>

                            <div class="content">
                                <span class="title">{{ $tarea->title }}</span>
                                <div class="task-meta">
                                    <span>Completada el {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            
                            <div class="actions">
                                <form action="/tareas/{{ $tarea->id ?? 0 }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea permanentemente?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif
        </div>
    </main>

    {{-- MODAL PRINCIPAL: Crear / Editar --}}
    <div class="modal-overlay" id="task-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="modal-titulo-principal">Nueva Tarea</h2>
                <button class="btn-close-modal" onclick="cerrarModal('task-modal')"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="{{ url('/task/create') }}" method="POST" id="form-tarea" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="metodo-formulario" value="POST">
                
                <div class="input-group">
                    <label class="input-label">TÍTULO</label>
                    <input type="text" name="title" id="input-titulo" required class="modern-input" placeholder="Ej. Estudiar para el examen...">
                </div>
                
                <div class="input-group">
                    <label class="input-label">DESCRIPCIÓN</label>
                    <textarea name="description" id="input-descripcion" rows="3" class="modern-input" placeholder="Detalles de la tarea..."></textarea>
                </div>

                <div class="input-group">
                    <label class="input-label">ETIQUETAS <small>(Ctrl + Clic para varias)</small></label>
                    <select name="labels[]" id="input-etiquetas" multiple class="modern-input select-multiple">
                        @foreach($labels ?? [] as $etiqueta)
                            <option value="{{ $etiqueta->id }}">{{ $etiqueta->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-row">
                    <div class="input-group half">
                        <label class="input-label">FECHA DE CREACIÓN</label>
                        <input type="date" name="fecha_asignacion" value="{{ date('Y-m-d') }}" readonly class="modern-input readonly-input">
                    </div>
                    <div class="input-group half">
                        <label class="input-label">FECHA LÍMITE</label>
                        <input type="date" name="due_date" id="input-fecha" class="modern-input">
                    </div>
                </div>

                <div class="modal-row">
                    <div class="input-group half">
                        <label class="input-label">PRIORIDAD</label>
                        <select name="priority" id="input-prioridad" class="modern-input">
                            <option value="low">Baja</option>
                            <option value="medium">Media</option>
                            <option value="high" selected>Alta</option>
                        </select>
                    </div>
                    <div class="input-group half">
                        <label class="input-label">ESTADO</label>
                        <select name="status" id="input-estado" class="modern-input">
                            <option value="pending" selected>Pendiente</option>
                            <option value="in_progress">En Progreso</option>
                            <option value="completed">Completada</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label"><i class="fas fa-paperclip"></i> ADJUNTAR ARCHIVO</label>
                    <input type="file" name="attachment" class="file-input" accept=".pdf,.doc,.docx,.jpg,.png">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-text" onclick="cerrarModal('task-modal')">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btn-submit-tarea">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL SECUNDARIO: Etiquetas --}}
    <div class="modal-overlay" id="label-modal">
        <div class="modal-card modal-sm">
            <div class="modal-header">
                <h2>Mis Etiquetas</h2>
                <button class="btn-close-modal" onclick="cerrarModal('label-modal')"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="{{ url('/label/create') }}" method="POST" class="tag-form">
                @csrf
                <div class="modal-row">
                    <div class="input-group half">
                        <label class="input-label">NOMBRE</label>
                        <input type="text" name="nombre" required class="modern-input" placeholder="Ej. Proyecto">
                    </div>
                    <div class="input-group half">
                        <label class="input-label">COLOR</label>
                        <input type="color" name="color" value="#2563eb" class="color-picker">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-100">Crear Nueva</button>
            </form>

            <div class="tag-manager">
                <label class="input-label">ETIQUETAS ACTUALES</label>
                <ul class="tag-list-manager">
                    @forelse($labels ?? [] as $etiqueta)
                        <li class="tag-item-manager" style="border-left-color: {{ $etiqueta->color }};">
                            <span>{{ $etiqueta->name }}</span>
                            <form action="{{ url('/labels') }}/{{ $etiqueta->id }}" method="POST" onsubmit="return confirm('¿Borrar etiqueta permanentemente?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon delete-tag"><i class="fas fa-trash"></i></button>
                            </form>
                        </li>
                    @empty
                        <p class="text-muted text-center"><small>No hay etiquetas creadas.</small></p>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <script>
        function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
        function abrirModalEtiquetas() { document.getElementById('label-modal').style.display = 'flex'; }
        
        function abrirModalNuevaTarea() {
            const form = document.getElementById('form-tarea');
            document.getElementById('modal-titulo-principal').innerText = 'Nueva Tarea';
            form.action = '{{ url('/task/create') }}';
            document.getElementById('metodo-formulario').value = 'POST';
            form.reset();
            
            document.getElementById('input-estado').value = 'pendiente';
            document.getElementById('btn-submit-tarea').innerText = 'Guardar Tarea';
            
            Array.from(document.getElementById('input-etiquetas').options).forEach(opt => opt.selected = false);
            document.getElementById('task-modal').style.display = 'flex';
        }

        function abrirModalEditar(elemento) {
            document.getElementById('modal-titulo-principal').innerText = 'Editar Tarea';
            document.getElementById('form-tarea').action = '/tareas/' + elemento.getAttribute('data-id');
            document.getElementById('metodo-formulario').value = 'PUT';
            
            document.getElementById('input-titulo').value = elemento.getAttribute('data-titulo');
            document.getElementById('input-descripcion').value = elemento.getAttribute('data-descripcion');
            document.getElementById('input-fecha').value = elemento.getAttribute('data-fecha_limite');
            document.getElementById('input-prioridad').value = elemento.getAttribute('data-prioridad');
            document.getElementById('input-estado').value = elemento.getAttribute('data-estado');
            
            let etiquetas = JSON.parse(elemento.getAttribute('data-etiquetas') || '[]');
            Array.from(document.getElementById('input-etiquetas').options).forEach(opt => {
                opt.selected = etiquetas.includes(parseInt(opt.value));
            });

            document.getElementById('btn-submit-tarea').innerText = 'Actualizar Cambios';
            document.getElementById('task-modal').style.display = 'flex';
        }
    </script>
</body>
</html>