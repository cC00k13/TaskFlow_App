<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Mis Tareas</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    {{-- Lógica de Tareas (Filtros, Ordenamiento y Estados) --}}
    @php
        $listaTareas = collect($tasks ?? []);

        // 1. APLICAR FILTRO DE PRIORIDAD
        $prioridadFiltro = request('filter_priority', 'todas');
        if($prioridadFiltro !== 'todas') {
            $listaTareas = $listaTareas->where('priority', $prioridadFiltro);
        }

        // 2. DEFINIR ORDENAMIENTO
        $orden = request('ordenar_por', 'fecha_asc');
        
        $ordenarPorFechaAsc = function($tarea) {
            return empty($tarea->due_date) ? '9999-12-31' : $tarea->due_date;
        };
        $ordenarPorFechaDesc = function($tarea) {
            return empty($tarea->due_date) ? '0000-00-00' : $tarea->due_date;
        };

        if($orden === 'fecha_desc') {
            $listaTareas = $listaTareas->sortByDesc($ordenarPorFechaDesc);
        } elseif($orden === 'mas_recientes') {
            $listaTareas = $listaTareas->reverse(); 
        } else {
            $listaTareas = $listaTareas->sortBy($ordenarPorFechaAsc);
        }

        // 3. DIVIDIR EN ESTADOS
        $pendientes = $listaTareas->where('status', 'pending');
        $enProgreso = $listaTareas->where('status', 'in_progress');
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
                
                {{-- CONTROLES DE FILTRO Y ORDEN --}}
                <div class="filters-and-summary">
                    <div class="filter-bar">
                        <form action="{{ route('dashboard') }}" method="GET" class="form-inline" style="display: flex; gap: 10px; align-items: center;" id="form-filtros">
                            <input type="hidden" name="ordenar_por" id="input-orden-actual" value="{{ $orden }}">

                            <label><i class="fas fa-filter"></i> Prioridad:</label>
                            <select name="filter_priority" class="select-ordenar" onchange="document.getElementById('form-filtros').submit();" style="min-width: 120px;">
                                <option value="todas" {{ $prioridadFiltro == 'todas' ? 'selected' : '' }}>Todas</option>
                                <option value="high" {{ $prioridadFiltro == 'high' ? 'selected' : '' }}>Alta</option>
                                <option value="medium" {{ $prioridadFiltro == 'medium' ? 'selected' : '' }}>Media</option>
                                <option value="low" {{ $prioridadFiltro == 'low' ? 'selected' : '' }}>Baja</option>
                            </select>

                            @php
                                $siguienteOrden = ($orden == 'fecha_asc') ? 'fecha_desc' : 'fecha_asc';
                                $iconoOrden = ($orden == 'fecha_asc') ? 'fa-sort-numeric-down' : 'fa-sort-numeric-up-alt';
                            @endphp
                            
                            <button type="button" class="btn-outline" style="display: flex; gap: 8px; align-items: center; background: white;" title="Alternar orden de fechas" onclick="document.getElementById('input-orden-actual').value = '{{ $siguienteOrden }}'; document.getElementById('form-filtros').submit();">
                                <i class="fas {{ $iconoOrden }}" style="color: var(--primary);"></i> 
                                <span>{{ $orden == 'fecha_asc' ? 'Próximas a vencer' : 'Más lejanas a vencer' }}</span>
                            </button>
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
                                data-etiquetas="{{ json_encode(isset($tarea->labels) ? $tarea->labels->pluck('id') : []) }}"
                                data-archivo="{{ $tarea->attachment ? basename($tarea->attachment) : '' }}">

                                <span class="title">{{ $tarea->title }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->priority ?? 'medium') }}">
                                        @if($tarea->priority == 'high') ALTA
                                        @elseif($tarea->priority == 'low') BAJA
                                        @else MEDIA
                                        @endif
                                    </span>
                                    
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
                                    
                                    @if(!empty($tarea->attachment))
                                        <a href="{{ url('/task/' . $tarea->id . '/download') }}" target="_blank" class="meta-file" title="Abrir archivo" onclick="event.stopPropagation();" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                            <i class="fas fa-paperclip"></i> Ver Evidencia
                                        </a>
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
                                data-etiquetas="{{ json_encode(isset($tarea->labels) ? $tarea->labels->pluck('id') : []) }}"
                                data-archivo="{{ $tarea->attachment ? basename($tarea->attachment) : '' }}">

                                <span class="title">{{ $tarea->title }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->priority ?? 'medium') }}">
                                        @if($tarea->priority == 'high') ALTA
                                        @elseif($tarea->priority == 'low') BAJA
                                        @else MEDIA
                                        @endif
                                    </span>

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
                                    
                                    @if(!empty($tarea->attachment))
                                        <a href="{{ url('/task/' . $tarea->id . '/download') }}" target="_blank" class="meta-file" title="Abrir archivo" onclick="event.stopPropagation();" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                            <i class="fas fa-paperclip"></i> Ver Evidencia
                                        </a>
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
                            <div class="content" onclick="abrirModalEditar(this)"
                                data-id="{{ $tarea->id }}" data-titulo="{{ $tarea->title }}" 
                                data-descripcion="{{ $tarea->description }}" data-fecha_limite="{{ $tarea->due_date }}" 
                                data-prioridad="{{ $tarea->priority }}" data-estado="{{ $tarea->status }}"
                                data-etiquetas="{{ json_encode(isset($tarea->labels) ? $tarea->labels->pluck('id') : []) }}"
                                data-archivo="{{ $tarea->attachment ? basename($tarea->attachment) : '' }}">
                                
                                <span class="title">{{ $tarea->title }}</span>
                                <div class="task-meta">
                                    <span>Completada el {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                                    
                                    @if(!empty($tarea->attachment))
                                        <a href="{{ url('/task/' . $tarea->id . '/download') }}" target="_blank" class="meta-file" title="Abrir archivo" onclick="event.stopPropagation();" style="color: #2563eb; text-decoration: none; font-weight: 500; margin-left: 15px;">
                                            <i class="fas fa-paperclip"></i> Evidencia
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="actions">
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="return confirm('¿Eliminar permanentemente?');">
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
                <input type="hidden" name="_method" id="metodo-formulario" value="{{ old('_method', 'POST') }}">
                
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
                    <textarea name="description" id="input-descripcion" rows="3" class="modern-input" placeholder="Detalles de la tarea...">{{ old('description') }}</textarea>
                </div>

                <div class="input-group">
                    <label class="input-label">CATEGORÍAS / ETIQUETAS</label>
                    <div class="labels-grid-selector">
                        @forelse($labels ?? [] as $etiqueta)
                            <label class="label-checkbox-wrapper" title="{{ $etiqueta->name }}">
                                <input type="checkbox" name="labels[]" value="{{ $etiqueta->id }}" class="label-checkbox-input"
                                       {{ (is_array(old('labels')) && in_array($etiqueta->id, old('labels'))) ? 'checked' : '' }}>
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
                        <input type="date" name="due_date" id="input-fecha" class="modern-input" value="{{ old('due_date') }}">
                    </div>
                </div>

                <div class="modal-row">
                    <div class="input-group half">
                        <label class="input-label">PRIORIDAD</label>
                        <select name="priority" id="input-prioridad" class="modern-input">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                        </select>
                    </div>
                    <div class="input-group half">
                        <label class="input-label">ESTADO</label>
                        <select name="status" id="input-estado" class="modern-input">
                            <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                        </select>
                    </div>
                </div>

                {{-- CARGA Y VALIDACIÓN VISUAL DE ARCHIVO --}}
                <div class="input-group">
                    <label class="input-label"><i class="fas fa-paperclip"></i> ADJUNTAR EVIDENCIA</label>
                    <input type="file" name="attachment" class="file-input" accept=".pdf,.doc,.docx,.jpg,.png">
                    
                    {{-- AVISO DE BORRADOR PARA ARCHIVOS --}}
                    @if($errors->any())
                        <div style="margin-top: 8px; font-size: 0.8rem; color: #d97706; background: #fef3c7; padding: 6px 10px; border-radius: 4px; border: 1px solid #fcd34d;">
                            <i class="fas fa-exclamation-triangle"></i> Por seguridad del navegador, <strong>debes volver a seleccionar tu archivo</strong>.
                        </div>
                    @endif

                    <div id="archivo-actual-container" style="display: none; margin-top: 8px; font-size: 0.85rem; color: #059669; background: #ecfdf5; padding: 8px 12px; border-radius: 6px; border: 1px solid #a7f3d0;">
                        <i class="fas fa-check-circle"></i> Archivo actual guardado: <strong id="nombre-archivo-actual"></strong>
                    </div>
                    
                    @error('attachment')
                        <span class="validation-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 5px; display: block;">
                            {{ $message }}
                        </span>
                    @enderror
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
                
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 6px; display: block;">NOMBRE</label>
                        <div style="position: relative;">
                            <input type="text" name="nombre" id="input-nombre-etiqueta" required 
                                   class="modern-input @error('nombre') is-invalid @enderror" 
                                   placeholder="Ej. Proyecto" value="{{ old('nombre') }}" maxlength="30"
                                   style="width: 100%; padding: 10px 45px 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; height: 42px; box-sizing: border-box;">
                            <small id="contador-etiqueta" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.75rem; pointer-events: none;">0/30</small>
                        </div>
                        <small id="mensaje-limite-etiqueta" style="color: #ef4444; display: none; font-size: 0.75rem; margin-top: 4px;">Límite alcanzado</small>
                        @error('nombre')
                            <div class="validation-error alert-backend" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="input-group" style="flex: 1; margin-bottom: 0; position: relative;">
                        <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 6px; display: block;">COLOR</label>
                        
                        <input type="hidden" name="color" id="input-color-etiqueta" value="{{ old('color', '#3b82f6') }}">

                        <button type="button" id="color-picker-trigger" class="modern-input" style="display: flex; align-items: center; justify-content: space-between; height: 42px; cursor: pointer; padding: 5px 12px; background: white;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="color-picker-preview" style="width: 18px; height: 18px; border-radius: 50%; background-color: {{ old('color', '#3b82f6') }}; border: 1px solid #d1d5db; box-shadow: 0 1px 2px rgba(0,0,0,0.1);"></span>
                                <span id="color-picker-text" style="font-size: 0.85rem; color: #4b5563; font-weight: 500;">{{ strtoupper(old('color', '#3B82F6')) }}</span>
                            </div>
                            <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: #9ca3af;"></i>
                        </button>

                        <div id="color-picker-dropdown" class="hide" style="position: absolute; top: calc(100% + 5px); left: 0; width: 220px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); z-index: 50;">
                            <div class="color-palette" style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-start;">
                                @php
                                    $coloresForm = ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#64748b'];
                                @endphp
                                @foreach($coloresForm as $c)
                                    <div class="color-swatch {{ old('color', '#3b82f6') === $c ? 'selected' : '' }}" 
                                         data-color="{{ $c }}" 
                                         style="width: 24px; height: 24px; border-radius: 50%; background-color: {{ $c }}; cursor: pointer; transition: transform 0.1s; position: relative;" title="{{ $c }}">
                                    </div>
                                @endforeach

                                <div class="custom-color-wrapper" style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden; cursor: pointer; position: relative; background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red);" title="Color personalizado">
                                    <input type="color" id="custom-color-picker" value="{{ old('color', '#3b82f6') }}" style="opacity: 0; position: absolute; width: 100%; height: 100%; cursor: pointer; border: none; padding: 0;">
                                </div>
                            </div>
                        </div>
                        
                        @error('color')
                            <span class="validation-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 5px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" id="btn-submit-etiqueta" style="width: 100%; padding: 10px; border-radius: 6px; font-weight: 600;">Crear Nueva</button>
                <button type="button" class="btn-text hide" id="btn-cancelar-etiqueta" onclick="resetearFormularioEtiquetas()" style="width: 100%; padding: 10px; margin-top: 5px; text-align: center; color: #6b7280; background: none; border: none; cursor: pointer;">Cancelar Edición</button>
            </form>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">

            <div class="tag-manager-wrapper">
                <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 10px; display: block;">ETIQUETAS ACTUALES</label>
                <ul class="tag-list-manager" style="max-height: 220px; overflow-y: auto; padding: 0; margin: 0; list-style: none; padding-right: 5px;">
                    @forelse($labels ?? [] as $etiqueta)
                        <li class="tag-item-manager" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; margin-bottom: 8px; border-radius: 6px; border-left: 4px solid {{ $etiqueta->color }}; border-top: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;">
                            <div class="tag-info-display" style="display: flex; align-items: center; gap: 10px;">
                                <div class="color-indicator" style="width: 12px; height: 12px; border-radius: 50%; background-color: {{ $etiqueta->color }};"></div>
                                <span class="tag-text" style="font-weight: 500; color: #374151;">{{ $etiqueta->name }}</span>
                            </div>
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
    @include('partials.scripts')
</body>
</html>