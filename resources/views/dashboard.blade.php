<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Mis Tareas</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- Librería SortableJS para Arrastrar y Soltar --}}
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
    <div id="toast-error" class="toast-notification toast-error" style="background-color: #ef4444; color: white; border-left-color: #b91c1c;">
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
                    <form action="{{ route('logout') }}" method="POST" class="form-inline" style="display: inline;">
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
                        <form action="{{ route('dashboard') }}" method="GET" class="form-inline" style="display: inline;">
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
                                        <span class="tag tag-custom" style="background-color: {{ $etiqueta->color ?? '#eee' }}; color: #fff;">{{ $etiqueta->name }}</span>
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
                                        <span class="tag tag-custom" style="background-color: {{ $etiqueta->color ?? '#eee' }}; color: #fff;">{{ $etiqueta->name }}</span>
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
         MODAL SECUNDARIO: PANEL CRUD DE ETIQUETAS (UI MEJORADA)
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
                
                {{-- Contenedor Flex alineado desde arriba --}}
                <div style="display: flex; gap: 15px; align-items: flex-start; margin-bottom: 15px;">
                    
                    {{-- Grupo NOMBRE --}}
                    <div class="input-group" style="flex: 2; margin-bottom: 0;">
                        <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 6px; display: block;">NOMBRE</label>
                        
                        {{-- Truco UI: Posición relativa para meter el contador dentro --}}
                        <div style="position: relative;">
                            <input type="text" name="nombre" id="input-nombre-etiqueta" required 
                                   class="modern-input @error('nombre') is-invalid @enderror" 
                                   placeholder="Ej. Proyecto" value="{{ old('nombre') }}" maxlength="30"
                                   style="width: 100%; padding: 10px 45px 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; height: 42px; box-sizing: border-box;">
                            
                            {{-- Contador flotante dentro del input --}}
                            <small id="contador-etiqueta" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.75rem; pointer-events: none;">0/30</small>
                        </div>
                        
                        <small id="mensaje-limite-etiqueta" style="color: #ef4444; display: none; font-size: 0.75rem; margin-top: 4px;">Límite alcanzado</small>
                        
                        @error('nombre')
                            <div class="validation-error alert-backend" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Grupo COLOR --}}
                    <div class="input-group" style="flex: 1; margin-bottom: 0;">
                        <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 6px; display: block;">COLOR</label>
                        
                        {{-- Altura forzada a 42px para que coincida exactamente con el input de texto --}}
                        <input type="color" name="color" id="input-color-etiqueta" 
                               value="{{ old('color', '#2563eb') }}" 
                               style="width: 100%; height: 42px; padding: 2px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; background: white; box-sizing: border-box;">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" id="btn-submit-etiqueta" style="width: 100%; padding: 10px; border-radius: 6px; font-weight: 600;">Crear Nueva</button>
                <button type="button" class="btn-text hide" id="btn-cancelar-etiqueta" onclick="resetearFormularioEtiquetas()" style="width: 100%; padding: 10px; margin-top: 5px; text-align: center; color: #6b7280; background: none; border: none; cursor: pointer;">Cancelar Edición</button>
            </form>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">

            <div class="tag-manager-wrapper">
                <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 10px; display: block;">ETIQUETAS ACTUALES</label>
                
                {{-- Contenedor con scroll (overflow-y) para que el modal no se estire infinitamente si hay muchas etiquetas --}}
                <ul class="tag-list-manager" style="max-height: 220px; overflow-y: auto; padding: 0; margin: 0; list-style: none; padding-right: 5px;">
                    @forelse($labels ?? [] as $etiqueta)
                        <li class="tag-item-manager" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; margin-bottom: 8px; border-radius: 6px; border-left: 4px solid {{ $etiqueta->color }}; border-top: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;">
                            
                            <div class="tag-info-display" style="display: flex; align-items: center; gap: 10px;">
                                <div class="color-indicator" style="width: 12px; height: 12px; border-radius: 50%; background-color: {{ $etiqueta->color }};"></div>
                                <span class="tag-text" style="font-weight: 500; color: #374151;">{{ $etiqueta->name }}</span>
                            </div>
                            
                            {{-- Aseguramos que los íconos de editar y borrar se vean limpios --}}
                            <div class="actions" style="display: flex; gap: 10px;">
                                <button type="button" title="Editar" style="background: none; border: none; cursor: pointer; color: #9ca3af; transition: color 0.2s;" onmouseover="this.style.color='#4f46e5'" onmouseout="this.style.color='#9ca3af'" onclick="editarEtiqueta('{{ $etiqueta->id }}', '{{ $etiqueta->name }}', '{{ $etiqueta->color }}')">
                                    <i class="fas fa-pen"></i>
                                </button>
                                
                                <form action="{{ url('/labels') }}/{{ $etiqueta->id }}" method="POST" style="margin: 0;" onsubmit="return confirm('¿Borrar etiqueta permanentemente?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Eliminar" style="background: none; border: none; cursor: pointer; color: #9ca3af; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <p class="text-center" style="padding: 20px; font-style: italic; color: #9ca3af; font-size: 0.9rem;">No hay etiquetas creadas.</p>
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
        // Lógica de Contador de Caracteres (Etiquetas)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const inputNombre = document.getElementById('input-nombre-etiqueta');
            const contador = document.getElementById('contador-etiqueta');
            const mensajeLimite = document.getElementById('mensaje-limite-etiqueta');
            const limite = 30;

            if(inputNombre && contador && mensajeLimite) {
                const actualizarContador = () => {
                    const longitudActual = inputNombre.value.length;
                    contador.innerText = `${longitudActual}/${limite}`;

                    if (longitudActual >= limite) {
                        contador.style.color = '#ef4444'; // Rojo
                        contador.style.fontWeight = 'bold';
                        mensajeLimite.style.display = 'block';
                    } else {
                        contador.style.color = '#9ca3af'; // Gris
                        contador.style.fontWeight = 'normal';
                        mensajeLimite.style.display = 'none';
                    }
                };

                inputNombre.addEventListener('input', actualizarContador);
                actualizarContador(); // Ejecutar al inicio por si hay error de validación previo
            }
        });

        // ==========================================
        // Lógica Drag and Drop (Arrastrar)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const sortableOptions = {
                group: 'tareas', 
                animation: 150, 
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost', 
                
                onEnd: function (evt) {
                    const itemEl = evt.item;  
                    const toList = evt.to;    
                    
                    const taskId = itemEl.getAttribute('data-id');
                    const newStatus = toList.getAttribute('data-status');
                    
                    const checkbox = itemEl.querySelector('.task-check');
                    const hiddenInput = itemEl.querySelector('input[name="status"]'); 
                    const contentDiv = itemEl.querySelector('.content'); 

                    if(contentDiv) {
                        contentDiv.setAttribute('data-estado', newStatus);
                    }

                    if (newStatus === 'completed') {
                        itemEl.classList.add('completed');
                        itemEl.style.borderLeftColor = ''; 
                        if(checkbox) { checkbox.checked = true; checkbox.title = "Devolver a pendientes"; }
                        if(hiddenInput) hiddenInput.value = 'pending'; 
                    } else {
                        itemEl.classList.remove('completed');
                        if (newStatus === 'in_progress') {
                            itemEl.style.borderLeftColor = '#0284c7'; 
                        } else {
                            itemEl.style.borderLeftColor = ''; 
                        }
                        if(checkbox) { checkbox.checked = false; checkbox.title = "Marcar como completada"; }
                        if(hiddenInput) hiddenInput.value = 'completed';
                    }

                    // Ordenamiento Automático
                    const itemsEnLista = Array.from(toList.querySelectorAll('.task-item'));
                    itemsEnLista.sort((a, b) => {
                        const contentA = a.querySelector('.content');
                        const contentB = b.querySelector('.content');
                        
                        let fechaA = contentA ? contentA.getAttribute('data-fecha_limite') : '';
                        let fechaB = contentB ? contentB.getAttribute('data-fecha_limite') : '';
                        
                        if (!fechaA) fechaA = '9999-12-31';
                        if (!fechaB) fechaB = '9999-12-31';
                        
                        return fechaA.localeCompare(fechaB);
                    });

                    itemsEnLista.forEach(item => toList.appendChild(item));

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

            new Sortable(document.getElementById('list-pending'), sortableOptions);
            new Sortable(document.getElementById('list-in_progress'), sortableOptions);
            new Sortable(document.getElementById('list-completed'), sortableOptions);
        });

        // ==========================================
        // Funciones de Modales Originales
        // ==========================================
        function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
        
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
            
            // Forzar disparo del contador al editar
            document.getElementById('input-nombre-etiqueta').dispatchEvent(new Event('input'));
        }

        function resetearFormularioEtiquetas() {
            const form = document.getElementById('form-etiqueta');
            document.getElementById('modal-titulo-etiqueta').innerText = 'Mis Etiquetas';
            form.action = '{{ url('/label/create') }}';
            document.getElementById('metodo-etiqueta').value = 'POST';
            form.reset();
            
            const inputName = document.getElementById('input-nombre-etiqueta');
            if(inputName) {
                inputName.classList.remove('is-invalid');
                inputName.dispatchEvent(new Event('input')); // Resetear contador a 0/30
            }

            document.getElementById('btn-submit-etiqueta').innerText = 'Crear Nueva';
            document.getElementById('btn-cancelar-etiqueta').classList.add('hide');
            
            // Ocultar alerta de backend si existe
            const alertBackend = document.querySelector('.alert-backend');
            if(alertBackend) alertBackend.style.display = 'none';
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