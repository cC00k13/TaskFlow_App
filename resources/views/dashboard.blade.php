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
    
    {{-- ==========================================
         FEEDBACK DE ESTADO (NOTIFICACIONES GLOBALES)
         ========================================== --}}
    
    @if(session('success'))
    <div id="toast-exito" class="toast-notification toast-success">
        <i class="fas fa-check-circle"></i> 
        <span>{{ session('success') }}</span>
    </div>
    <script>setTimeout(() => { let t = document.getElementById('toast-exito'); if(t) { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); } }, 3500);</script>
    @endif

    @if($errors->any())
    <div id="toast-error" class="toast-notification toast-error">
        <i class="fas fa-exclamation-circle"></i> 
        <span>Por favor, corrige los errores en el formulario.</span>
    </div>
    <script>setTimeout(() => { let t = document.getElementById('toast-error'); if(t) { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); } }, 4500);</script>
    @endif

    {{-- Lógica de Tareas --}}
    @php
        $listaTareas = collect($tasks ?? []);
        $pendientes = $listaTareas->where('status', '!=', 'completed')->sortBy(function($tarea) {
            return empty($tarea->due_date) ? '9999-12-31' : $tarea->due_date;
        });
        $completadas = $listaTareas->where('status', 'completed');
    @endphp

    <main class="dashboard-container">
        <div class="dashboard-card">
            
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
                    <form action="{{ route('logout') }}" method="POST" class="form-inline">
                        @csrf
                        <button type="submit" class="logout-icon" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></button>
                    </form>
                </div>
            </header>

            <section class="controls">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Buscar tareas...">
                    <button class="btn-primary" onclick="abrirModalNuevaTarea()">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                </div>
                
                <div class="filters-and-summary">
                    <div class="filter-bar">
                        <label><i class="fas fa-sort-amount-down"></i> Ordenar por:</label>
                        <form action="{{ route('dashboard') }}" method="GET" class="form-inline">
                            <select name="ordenar_por" class="select-ordenar" onchange="this.form.submit()">
                                <option value="fecha_proxima" {{ request('ordenar_por') == 'fecha_proxima' ? 'selected' : '' }}>Más próximas a vencer</option>
                                <option value="mas_recientes" {{ request('ordenar_por') == 'mas_recientes' ? 'selected' : '' }}>Más recientes</option>
                                <option value="prioridad_alta" {{ request('ordenar_por') == 'prioridad_alta' ? 'selected' : '' }}>Prioridad más alta</option>
                            </select>
                        </form>
                    </div>
                    <span class="badge-pending">{{ $pendientes->count() }} Tareas Pendientes</span>
                </div>
            </section>

            <section class="task-section">
                <h3 class="section-title">En Curso</h3>
                <ul class="task-list">
                    @forelse($pendientes as $tarea)
                        <li class="task-item">
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="status" value="completed">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" title="Marcar como completada">
                            </form>
                            
                            <div class="content" onclick="abrirModalEditar(this)" 
                                data-id="{{ $tarea->id }}" data-titulo="{{ $tarea->title }}" 
                                data-descripcion="{{ $tarea->description }}" data-fecha_limite="{{ $tarea->due_date }}" 
                                data-prioridad="{{ $tarea->priority }}" data-estado="{{ $tarea->status }}"
                                data-etiquetas="{{ json_encode(isset($tarea->labels) ? $tarea->labels->pluck('id') : []) }}">

                                <span class="title">{{ $tarea->title }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->priority ?? 'medium') }}">{{ strtoupper($tarea->priority ?? 'MEDIA') }}</span>
                                    @foreach($tarea->labels ?? [] as $etiqueta)
                                        <span class="tag tag-custom" style="background-color: {{ $etiqueta->color ?? '#eee' }};">{{ $etiqueta->name }}</span>
                                    @endforeach
                                </div>
                                <div class="task-meta">
                                    @if(!empty($tarea->due_date))
                                        <span class="meta-date {{ \Carbon\Carbon::parse($tarea->due_date)->isPast() ? 'overdue' : '' }}">
                                            <i class="far fa-clock"></i> {{ $tarea->due_date }}
                                        </span>
                                    @endif
                                    @if(!empty($tarea->attachment))
                                        <span class="meta-file"><i class="fas fa-paperclip"></i> Archivo adjunto</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn-icon edit" onclick="abrirModalEditar(this.parentElement.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea permanentemente?');">
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

            @if($completadas->count() > 0)
            <section class="task-section mt-4">
                <h3 class="section-title text-muted">Completadas</h3>
                <ul class="task-list">
                    @foreach($completadas as $tarea)
                        <li class="task-item completed">
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="status" value="pending">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" checked title="Desmarcar">
                            </form>
                            <div class="content">
                                <span class="title">{{ $tarea->title }}</span>
                                <div class="task-meta">
                                    <span>Completada el {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <div class="actions">
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea permanentemente?');">
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

    {{-- ==========================================
         MODAL PRINCIPAL: Crear / Editar Tareas
         ========================================== --}}
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

                {{-- SELECTOR VISUAL DE ETIQUETAS --}}
                <div class="input-group">
                    <label class="input-label">CATEGORÍAS / ETIQUETAS</label>
                    <div class="labels-grid-selector">
                        @forelse($labels ?? [] as $etiqueta)
                            <label class="label-checkbox-wrapper" title="{{ $etiqueta->name }}">
                                <input type="checkbox" name="labels[]" value="{{ $etiqueta->id }}" class="label-checkbox-input">
                                <span class="label-pill" style="--tag-color: {{ $etiqueta->color }};">
                                    <span class="color-dot" style="background-color: {{ $etiqueta->color }};"></span>
                                    <span class="tag-name-text">{{ $etiqueta->name }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="empty-labels-msg">
                                <i class="fas fa-info-circle"></i> No tienes etiquetas. Crea una desde "Mis Etiquetas".
                            </p>
                        @endforelse
                    </div>
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
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
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

    {{-- ==========================================
         MODAL SECUNDARIO: PANEL CRUD DE ETIQUETAS
         ========================================== --}}
    <div class="modal-overlay" id="label-modal">
        <div class="modal-card modal-sm label-modal-card">
            <div class="modal-header">
                <h2 id="modal-titulo-etiqueta">Mis Etiquetas</h2>
                <button class="btn-close-modal" onclick="cerrarModal('label-modal')"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="{{ url('/label/create') }}" method="POST" class="tag-form" id="form-etiqueta">
                @csrf
                <input type="hidden" name="_method" id="metodo-etiqueta" value="POST">
                
                <div class="modal-row gap-10">
                    <div class="input-group flex-1">
                        <label class="input-label">NOMBRE</label>
                        <input type="text" name="nombre" id="input-nombre-etiqueta" required 
                               class="modern-input @error('nombre') is-invalid @enderror" 
                               placeholder="Ej. Proyecto" value="{{ old('nombre') }}">
                               
                        @error('nombre')
                            <span class="validation-error">
                                <i class="fas fa-info-circle"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>
                    <div class="input-group color-input-group">
                        <label class="input-label">COLOR</label>
                        <input type="color" name="color" id="input-color-etiqueta" 
                               value="{{ old('color', '#2563eb') }}" class="modern-input color-picker-input">
                               
                        @error('color')
                            <span class="validation-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>
                
                <button type="submit" class="btn-primary btn-full mt-10" id="btn-submit-etiqueta">Crear Nueva</button>
                <button type="button" class="btn-text btn-full mt-10 text-center hide" id="btn-cancelar-etiqueta" onclick="resetearFormularioEtiquetas()">Cancelar Edición</button>
            </form>

            <div class="tag-manager-wrapper">
                <label class="input-label manager-title">ETIQUETAS ACTUALES</label>
                <ul class="tag-list-manager">
                    @forelse($labels ?? [] as $etiqueta)
                        <li class="tag-item-manager" style="border-left-color: {{ $etiqueta->color }};">
                            
                            <div class="tag-info-display">
                                <div class="color-indicator" style="background-color: {{ $etiqueta->color }};"></div>
                                <span class="tag-text">{{ $etiqueta->name }}</span>
                            </div>
                            
                            {{-- AQUÍ APLICAMOS LA CLASE 'actions' ORIGINAL DEL DASHBOARD --}}
                            <div class="actions">
                                <button type="button" class="btn-icon edit" title="Editar" onclick="editarEtiqueta('{{ $etiqueta->id }}', '{{ $etiqueta->name }}', '{{ $etiqueta->color }}')">
                                    <i class="fas fa-pen"></i>
                                </button>
                                
                                <form action="{{ url('/labels') }}/{{ $etiqueta->id }}" method="POST" class="form-inline" onsubmit="return confirm('¿Borrar etiqueta permanentemente?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <p class="empty-labels-text">No hay etiquetas creadas.</p>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ==========================================
         SCRIPTS GLOBALES
         ========================================== --}}
    <script>
        function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
        
        // --- Lógica Modal Etiquetas ---
        function abrirModalEtiquetas() { 
            @if(!$errors->has('nombre') && !$errors->has('color'))
                resetearFormularioEtiquetas();
            @endif
            document.getElementById('label-modal').style.display = 'flex'; 
        }

        @if($errors->has('nombre') || $errors->has('color'))
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById('label-modal').style.display = 'flex';
            });
        @endif
        
        function editarEtiqueta(id, nombre, color) {
            document.getElementById('modal-titulo-etiqueta').innerText = 'Editar Etiqueta';
            document.getElementById('form-etiqueta').action = '/labels/' + id;
            document.getElementById('metodo-etiqueta').value = 'PUT';
            document.getElementById('input-nombre-etiqueta').value = nombre;
            document.getElementById('input-color-etiqueta').value = color;
            document.getElementById('btn-submit-etiqueta').innerText = 'Guardar Cambios';
            document.getElementById('btn-cancelar-etiqueta').classList.remove('hide');
            document.getElementById('input-nombre-etiqueta').focus();
        }

        function resetearFormularioEtiquetas() {
            const form = document.getElementById('form-etiqueta');
            document.getElementById('modal-titulo-etiqueta').innerText = 'Mis Etiquetas';
            form.action = '{{ url('/label/create') }}';
            document.getElementById('metodo-etiqueta').value = 'POST';
            form.reset();
            
            const inputName = document.getElementById('input-nombre-etiqueta');
            if(inputName) inputName.classList.remove('is-invalid');

            document.getElementById('btn-submit-etiqueta').innerText = 'Crear Nueva';
            document.getElementById('btn-cancelar-etiqueta').classList.add('hide');
        }

        // --- Lógica Modal Tareas ---
        function abrirModalNuevaTarea() {
            const form = document.getElementById('form-tarea');
            document.getElementById('modal-titulo-principal').innerText = 'Nueva Tarea';
            form.action = '{{ url('/task/create') }}';
            document.getElementById('metodo-formulario').value = 'POST';
            form.reset();
            
            document.getElementById('input-estado').value = 'pending';
            document.getElementById('btn-submit-tarea').innerText = 'Guardar Tarea';
            
            document.querySelectorAll('.label-checkbox-input').forEach(cb => cb.checked = false);
            document.getElementById('task-modal').style.display = 'flex';
        }

        function abrirModalEditar(elemento) {
            document.getElementById('modal-titulo-principal').innerText = 'Editar Tarea';
            document.getElementById('form-tarea').action = '/task/' + elemento.getAttribute('data-id');
            document.getElementById('metodo-formulario').value = 'PUT';
            
            document.getElementById('input-titulo').value = elemento.getAttribute('data-titulo');
            document.getElementById('input-descripcion').value = elemento.getAttribute('data-descripcion');
            document.getElementById('input-fecha').value = elemento.getAttribute('data-fecha_limite');
            document.getElementById('input-prioridad').value = elemento.getAttribute('data-prioridad');
            document.getElementById('input-estado').value = elemento.getAttribute('data-estado');
            
            let etiquetas = JSON.parse(elemento.getAttribute('data-etiquetas') || '[]');
            document.querySelectorAll('.label-checkbox-input').forEach(checkbox => {
                checkbox.checked = etiquetas.includes(parseInt(checkbox.value));
            });

            document.getElementById('btn-submit-tarea').innerText = 'Actualizar Cambios';
            document.getElementById('task-modal').style.display = 'flex';
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal-overlay') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>