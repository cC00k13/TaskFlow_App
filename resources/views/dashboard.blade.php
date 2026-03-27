<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Mis Tareas</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- NUEVO: Librería SortableJS para Arrastrar y Soltar --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- Para las peticiones AJAX --}}
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

    {{-- Lógica de Tareas (Dividido en 3 estados) --}}
    @php
        $listaTareas = collect($tasks ?? []);
        $ordenarPorFecha = function($tarea) {
            return empty($tarea->due_date) ? '9999-12-31' : $tarea->due_date;
        };

        $pendientes = $listaTareas->where('status', 'pending')->sortBy($ordenarPorFecha);
        $enProgreso = $listaTareas->where('status', 'in_progress')->sortBy($ordenarPorFecha);
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
                    <span class="badge-pending">{{ $pendientes->count() + $enProgreso->count() }} Tareas Activas</span>
                </div>
            </section>

            {{-- ==========================================
                 SECCIÓN 1: PENDIENTES
                 ========================================== --}}
            <section class="task-section">
                <h3 class="section-title">Pendientes</h3>
                {{-- NUEVO: id para SortableJS y data-status para saber a dónde se soltó --}}
                <ul class="task-list sortable-list" id="list-pending" data-status="pending" style="min-height: 50px; padding-bottom: 20px;">
                    @forelse($pendientes as $tarea)
                        <li class="task-item draggable-item" data-id="{{ $tarea->id }}">
                            <div class="drag-handle" title="Arrastrar"><i class="fas fa-grip-vertical"></i></div>
                            
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check" onclick="event.stopPropagation();">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="status" value="completed">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" title="Completar">
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
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn-icon edit" onclick="abrirModalEditar(this.parentElement.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="return confirm('¿Eliminar?');">
                                @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-list-ul"></i>
                            <p>No tienes tareas pendientes nuevas.</p>
                        </div>
                    @endforelse
                </ul>
            </section>

            {{-- ==========================================
                 SECCIÓN 2: EN PROGRESO
                 ========================================== --}}
            <section class="task-section mt-4">
                <h3 class="section-title" style="color: #0284c7; border-color: #0284c7;">En Progreso</h3>
                <ul class="task-list sortable-list" id="list-in_progress" data-status="in_progress" style="min-height: 50px; padding-bottom: 20px;">
                    @forelse($enProgreso as $tarea)
                        <li class="task-item draggable-item" style="border-left-color: #0284c7;" data-id="{{ $tarea->id }}">
                            <div class="drag-handle" title="Arrastrar"><i class="fas fa-grip-vertical"></i></div>
                            
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check" onclick="event.stopPropagation();">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="status" value="completed">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" title="Completar">
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
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn-icon edit" onclick="abrirModalEditar(this.parentElement.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="return confirm('¿Eliminar?');">
                                @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-spinner"></i>
                            <p>No tienes tareas en progreso.</p>
                        </div>
                    @endforelse
                </ul>
            </section>

            {{-- ==========================================
                 SECCIÓN 3: COMPLETADAS
                 ========================================== --}}
            <section class="task-section mt-4">
                <h3 class="section-title text-muted">Completadas</h3>
                <ul class="task-list sortable-list" id="list-completed" data-status="completed" style="min-height: 50px; padding-bottom: 20px;">
                    @forelse($completadas as $tarea)
                        <li class="task-item completed draggable-item" data-id="{{ $tarea->id }}">
                            <div class="drag-handle" title="Arrastrar"><i class="fas fa-grip-vertical"></i></div>
                            
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" class="task-form-check" onclick="event.stopPropagation();">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="status" value="pending">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" checked title="Devolver a pendientes">
                            </form>
                            <div class="content">
                                <span class="title">{{ $tarea->title }}</span>
                                <div class="task-meta">
                                    <span>Completada el {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <div class="actions">
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="return confirm('¿Eliminar?');">
                                @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        {{-- Espacio para soltar --}}
                    @endforelse
                </ul>
            </section>
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
                    <input type="text" name="title" id="input-titulo" required 
                           class="modern-input @error('title') is-invalid @enderror" 
                           placeholder="Ej. Estudiar para el examen..." value="{{ old('title') }}">
                           
                    {{-- Alerta formal de error (Gancho para la diseñadora) --}}
                    @error('title')
                        <span class="validation-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 5px; display: block; font-weight: 500;">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </span>
                    @enderror
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
                               placeholder="Ej. Proyecto" value="{{ old('nombre') }}" maxlength="30">
                               
                        {{-- Contenedor del contador listo para que diseño lo estilice --}}
                        <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                            <small id="mensaje-limite-etiqueta" style="color: #ef4444; display: none; font-size: 0.75rem; font-weight: 500;">Límite de caracteres alcanzado</small>
                            <small id="contador-etiqueta" style="color: #6b7280; font-size: 0.75rem; margin-left: auto;">0/30</small>
                        </div>

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
        const FECHA_HOY = "{{ date('Y-m-d') }}";
// ==========================================
        // NUEVO: Lógica de Contador de Caracteres (Etiquetas)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const inputNombre = document.getElementById('input-nombre-etiqueta');
            const contador = document.getElementById('contador-etiqueta');
            const mensajeLimite = document.getElementById('mensaje-limite-etiqueta');
            const limite = 30;

            if(inputNombre) {
                // Función para actualizar el contador
                const actualizarContador = () => {
                    const longitudActual = inputNombre.value.length;
                    contador.innerText = `${longitudActual}/${limite}`;

                    if (longitudActual >= limite) {
                        contador.style.color = '#ef4444'; 
                        mensajeLimite.style.display = 'block';
                    } else {
                        contador.style.color = '#6b7280'; 
                        mensajeLimite.style.display = 'none';
                    }
                };

                // Escuchar cuando el usuario escribe
                inputNombre.addEventListener('input', actualizarContador);
                
                // Ejecutar una vez al abrir para inicializar si ya hay texto (ej. al editar)
                actualizarContador(); 
            }
        });

        // ==========================================
        // NUEVO: Lógica Drag and Drop (Arrastrar)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar las 3 listas como arrastrables y conectadas entre sí
            const sortableOptions = {
                group: 'tareas', // Permite arrastrar entre diferentes listas
                animation: 150,  // Efecto visual suave
                handle: '.drag-handle', // Solo se arrastra desde el icono de los puntitos
                ghostClass: 'sortable-ghost', // Clase CSS mientras se arrastra
                
                // Función que se ejecuta cuando sueltas una tarea en otra lista
                onEnd: function (evt) {
                    const itemEl = evt.item;  // El elemento <li> que arrastramos
                    const toList = evt.to;    // La lista <ul> donde lo soltamos
                    
                    const taskId = itemEl.getAttribute('data-id');
                    const newStatus = toList.getAttribute('data-status');
                    
                    // Si se soltó en la misma lista, solo reordenamos
                    // Quitamos el "return;" para que, incluso si la sueltas en la misma lista, se auto-acomode por fecha.

                    // 1. Capturamos los elementos internos de la tarjeta
                    const checkbox = itemEl.querySelector('.task-check');
                    const hiddenInput = itemEl.querySelector('input[name="status"]'); 
                    const contentDiv = itemEl.querySelector('.content'); 

                    // 2. Actualizamos el dato oculto para el Modal de Edición
                    if(contentDiv) {
                        contentDiv.setAttribute('data-estado', newStatus);
                    }

                    // 3. Cambiamos los estilos según la columna
                    if (newStatus === 'completed') {
                        itemEl.classList.add('completed');
                        itemEl.style.borderLeftColor = ''; 
                        if(checkbox) { checkbox.checked = true; checkbox.title = "Devolver a pendientes"; }
                        if(hiddenInput) hiddenInput.value = 'pending'; 
                    } else {
                        itemEl.classList.remove('completed');
                        if (newStatus === 'in_progress') {
                            itemEl.style.borderLeftColor = '#0284c7'; // Azul
                        } else {
                            itemEl.style.borderLeftColor = ''; // Default
                        }
                        if(checkbox) { checkbox.checked = false; checkbox.title = "Marcar como completada"; }
                        if(hiddenInput) hiddenInput.value = 'completed';
                    }

                    // ==========================================
                    // 4. NUEVO: ORDENAMIENTO AUTOMÁTICO POR FECHA
                    // ==========================================
                    // Convertimos todos los <li> de la lista destino en un arreglo para poder ordenarlos
                    const itemsEnLista = Array.from(toList.querySelectorAll('.task-item'));
                    
                    itemsEnLista.sort((a, b) => {
                        const contentA = a.querySelector('.content');
                        const contentB = b.querySelector('.content');
                        
                        // Extraemos las fechas
                        let fechaA = contentA ? contentA.getAttribute('data-fecha_limite') : '';
                        let fechaB = contentB ? contentB.getAttribute('data-fecha_limite') : '';
                        
                        // Si no tienen fecha, les asignamos una en el año 9999 para que se vayan al fondo
                        if (!fechaA) fechaA = '9999-12-31';
                        if (!fechaB) fechaB = '9999-12-31';
                        
                        // Comparamos las fechas (las más próximas primero)
                        return fechaA.localeCompare(fechaB);
                    });

                    // Volvemos a inyectar los <li> en el <ul> en el orden correcto
                    // (El navegador los moverá visualmente de forma instantánea)
                    itemsEnLista.forEach(item => toList.appendChild(item));
                    // ==========================================

                    // 5. Petición AJAX (Fetch) al backend para guardar en BD (Solo si cambió de lista)
                    if (evt.from !== toList) {
                        fetch(`/tareas/${taskId}/estado-ajax`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(response => {
                            if(!response.ok) throw new Error('Error al actualizar');
                        })
                        .catch(error => {
                            console.error('Fallo al actualizar estado:', error);
                        });
                    }
                }
            };

            // Aplicar Sortable a las tres listas
            new Sortable(document.getElementById('list-pending'), sortableOptions);
            new Sortable(document.getElementById('list-in_progress'), sortableOptions);
            new Sortable(document.getElementById('list-completed'), sortableOptions);
        });

        // ==========================================
        // Funciones de Modales Originales
        // ==========================================
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
        
        // Mantener abierto el modal de Tareas si el título falla la validación
        @if($errors->has('title'))
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById('task-modal').style.display = 'flex';
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
            
            document.getElementById('input-fecha').setAttribute('min', FECHA_HOY);

            document.querySelectorAll('.label-checkbox-input').forEach(cb => cb.checked = false);
            document.getElementById('task-modal').style.display = 'flex';
        }

        function abrirModalEditar(elemento) {
            document.getElementById('modal-titulo-principal').innerText = 'Editar Tarea';
            document.getElementById('form-tarea').action = '/task/' + elemento.getAttribute('data-id');
            document.getElementById('metodo-formulario').value = 'PUT';
            
            let fechaLimite = elemento.getAttribute('data-fecha_limite');

            document.getElementById('input-titulo').value = elemento.getAttribute('data-titulo');
            document.getElementById('input-descripcion').value = elemento.getAttribute('data-descripcion');
            document.getElementById('input-fecha').value = fechaLimite;
            document.getElementById('input-prioridad').value = elemento.getAttribute('data-prioridad');
            document.getElementById('input-estado').value = elemento.getAttribute('data-estado');
            
            if (fechaLimite && fechaLimite < FECHA_HOY) {
                document.getElementById('input-fecha').setAttribute('min', fechaLimite);
            } else {
                document.getElementById('input-fecha').setAttribute('min', FECHA_HOY);
            }

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