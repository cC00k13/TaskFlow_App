<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Mis Tareas</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* ESTILOS PARA LA UI DE MÚLTIPLES ARCHIVOS */
        .file-preview-item { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; }
        .file-preview-info { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #334155; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; flex: 1; }
        .file-remove-btn { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1rem; padding: 4px; transition: color 0.2s; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 4px; }
        .file-remove-btn:hover { color: #b91c1c; background: #fee2e2; }
        .existing-file-item { background: #f1f5f9; border-color: #cbd5e1; }
    </style>
</head>
<body>
    
    @php
        $listaTareas = collect($tasks ?? []);

        $prioridadFiltro = request('filter_priority', 'todas');
        if($prioridadFiltro !== 'todas') {
            $listaTareas = $listaTareas->where('priority', $prioridadFiltro);
        }

        $orden = request('ordenar_por', 'fecha_asc');
        
        $ordenarPorFechaAsc = function($tarea) { return empty($tarea->due_date) ? '9999-12-31' : $tarea->due_date; };
        $ordenarPorFechaDesc = function($tarea) { return empty($tarea->due_date) ? '0000-00-00' : $tarea->due_date; };

        if($orden === 'fecha_desc') {
            $listaTareas = $listaTareas->sortByDesc($ordenarPorFechaDesc);
        } elseif($orden === 'mas_recientes') {
            $listaTareas = $listaTareas->reverse(); 
        } else {
            $listaTareas = $listaTareas->sortBy($ordenarPorFechaAsc);
        }

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
                    <button class="btn-outline" onclick="abrirModalEtiquetas()"><i class="fas fa-tags"></i> Mis Etiquetas</button>
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
                    <button class="btn-primary" onclick="abrirModalNuevaTarea()"><i class="fas fa-plus"></i> Nueva Tarea</button>
                </div>
                
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
                            
                            <button type="button" class="btn-outline" style="display: flex; gap: 8px; align-items: center; background: white;" title="Alternar orden" onclick="document.getElementById('input-orden-actual').value = '{{ $siguienteOrden }}'; document.getElementById('form-filtros').submit();">
                                <i class="fas {{ $iconoOrden }}" style="color: var(--primary);"></i> 
                                <span>{{ $orden == 'fecha_asc' ? 'Próximas a vencer' : 'Más lejanas a vencer' }}</span>
                            </button>
                        </form>
                    </div>
                    <span class="badge-pending">{{ $pendientes->count() + $enProgreso->count() }} Tareas Activas</span>
                </div>
            </section>

            {{-- SECCIÓN 1: PENDIENTES --}}
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
                                data-archivo="" 
                                data-archivos="{{ json_encode($tarea->attachments ?? []) }}">

                                <span class="title">{{ $tarea->title }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->priority ?? 'medium') }}">
                                        @if($tarea->priority == 'high') ALTA @elseif($tarea->priority == 'low') BAJA @else MEDIA @endif
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
                                    
                                    @if(!empty($tarea->attachments) && is_array($tarea->attachments))
                                        <div class="task-evidences" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                                            @foreach($tarea->attachments as $adjunto)
                                                <a href="{{ url('/task/' . $tarea->id . '/download?path=' . urlencode($adjunto['path'])) }}" target="_blank" class="meta-file" title="{{ $adjunto['original_name'] }}" onclick="event.stopPropagation();" style="color: #2563eb; text-decoration: none; font-size: 0.75rem; background: #eff6ff; padding: 4px 8px; border-radius: 4px; border: 1px solid #bfdbfe;">
                                                    <i class="fas fa-file-alt"></i> {{ \Illuminate\Support\Str::limit($adjunto['original_name'], 15) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn-icon edit" onclick="abrirModalEditar(this.parentElement.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="confirmarEliminacion(event, this, 'tarea')">
                                @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <div class="empty-state" style="text-align: center; padding: 40px 20px; background: #f8fafc; border-radius: 8px; border: 2px dashed #e2e8f0; margin-top: 10px;">
                            <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b; font-weight: 500; margin-bottom: 15px;">Aún no tienes tareas pendientes.</p>
                            <button type="button" class="btn-primary" onclick="abrirModalNuevaTarea()" style="padding: 8px 20px; font-size: 0.9rem; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fas fa-plus"></i> Crear mi primera tarea
                            </button>
                        </div>
                    @endforelse
                </ul>
            </section>

            {{-- SECCIÓN 2: EN PROGRESO --}}
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
                                data-archivo="" 
                                data-archivos="{{ json_encode($tarea->attachments ?? []) }}">

                                <span class="title">{{ $tarea->title }}</span>
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->priority ?? 'medium') }}">
                                        @if($tarea->priority == 'high') ALTA @elseif($tarea->priority == 'low') BAJA @else MEDIA @endif
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
                                    
                                    @if(!empty($tarea->attachments) && is_array($tarea->attachments))
                                        <div class="task-evidences" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                                            @foreach($tarea->attachments as $adjunto)
                                                <a href="{{ url('/task/' . $tarea->id . '/download?path=' . urlencode($adjunto['path'])) }}" target="_blank" class="meta-file" title="{{ $adjunto['original_name'] }}" onclick="event.stopPropagation();" style="color: #2563eb; text-decoration: none; font-size: 0.75rem; background: #eff6ff; padding: 4px 8px; border-radius: 4px; border: 1px solid #bfdbfe;">
                                                    <i class="fas fa-file-alt"></i> {{ \Illuminate\Support\Str::limit($adjunto['original_name'], 15) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn-icon edit" onclick="abrirModalEditar(this.parentElement.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="confirmarEliminacion(event, this, 'tarea')">
                                @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <div class="empty-state" style="text-align: center; padding: 30px 20px; color: #94a3b8;">
                            <i class="fas fa-tasks" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p style="font-size: 0.9rem;">No hay tareas en progreso.</p>
                            <p style="font-size: 0.8rem; margin-top: 5px;">Arrastra una tarea aquí para comenzar a trabajar en ella.</p>
                        </div>
                    @endforelse
                </ul>
            </section>

            {{-- SECCIÓN 3: COMPLETADAS --}}
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
                                data-archivo="" 
                                data-archivos="{{ json_encode($tarea->attachments ?? []) }}">
                                
                                <span class="title">{{ $tarea->title }}</span>
                                <div class="task-meta">
                                    <span>Completada el {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                                    
                                    @if(!empty($tarea->attachments) && is_array($tarea->attachments))
                                        <div class="task-evidences" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; margin-left: 15px;">
                                            @foreach($tarea->attachments as $adjunto)
                                                <a href="{{ url('/task/' . $tarea->id . '/download?path=' . urlencode($adjunto['path'])) }}" target="_blank" class="meta-file" title="{{ $adjunto['original_name'] }}" onclick="event.stopPropagation();" style="color: #64748b; text-decoration: none; font-size: 0.75rem; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                                    <i class="fas fa-file-alt"></i> {{ \Illuminate\Support\Str::limit($adjunto['original_name'], 15) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="actions">
                                <form action="{{ url('/task/' . $tarea->id) }}" method="POST" onsubmit="confirmarEliminacion(event, this, 'tarea')">
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

    {{-- MODAL PRINCIPAL: Crear / Editar Tareas --}}
    <div class="modal-overlay" id="task-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="modal-titulo-principal">Nueva Tarea</h2>
                <button type="button" class="btn-close-modal" onclick="cerrarModal('task-modal')"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="{{ url('/task/create') }}" method="POST" id="form-tarea" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="metodo-formulario" value="{{ old('_method', 'POST') }}">
                
                <div class="input-group">
                    <label class="input-label">TÍTULO</label>
                    <input type="text" name="title" id="input-titulo" required class="modern-input @error('title') is-invalid @enderror" placeholder="Ej. Estudiar para el examen..." value="{{ old('title') }}">
                    @error('title') <span class="validation-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 5px; display: block; font-weight: 500;"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span> @enderror
                </div>
                
                <div class="input-group">
                    <label class="input-label">DESCRIPCIÓN</label>
                    <textarea name="description" id="input-descripcion" rows="3" class="modern-input" placeholder="Detalles de la tarea...">{{ old('description') }}</textarea>
                </div>

                <div class="input-group">
                    <label class="input-label" style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <span><i class="fas fa-tags"></i> CATEGORÍAS (Opcional)</span>
                    </label>
                    
                    <div class="labels-search-container" style="position: relative; margin-bottom: 10px;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.85rem;"></i>
                        <input type="text" id="buscador-etiquetas" placeholder="Buscar etiqueta..." class="modern-input" style="padding-left: 35px; height: 36px; font-size: 0.85rem; width: 100%;">
                    </div>

                    <div class="labels-grid-selector" id="task-labels-container">
                        @forelse($labels ?? [] as $etiqueta)
                            <label class="label-checkbox-wrapper" title="{{ $etiqueta->name }}">
                                <input type="checkbox" name="labels[]" value="{{ $etiqueta->id }}" class="label-checkbox-input" {{ (is_array(old('labels')) && in_array($etiqueta->id, old('labels'))) ? 'checked' : '' }}>
                                <span class="label-pill" style="--tag-color: {{ $etiqueta->color }};">
                                    <span class="color-dot" style="background-color: {{ $etiqueta->color }};"></span>
                                    <span class="label-text-content">{{ $etiqueta->name }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="empty-labels-msg"><i class="fas fa-info-circle"></i> No tienes etiquetas. Crea una desde "Mis Etiquetas".</p>
                        @endforelse
                        <div id="msg-busqueda-vacia" class="empty-labels-msg" style="display: none; width: 100%; text-align: center; color: #94a3b8;"><i class="fas fa-search-minus"></i> No se encontraron etiquetas.</div>
                    </div>
                </div>

                <div class="modal-row">
                    <div class="input-group half">
                        <label class="input-label">FECHA DE CREACIÓN</label>
                        <input type="date" name="fecha_asignacion" value="{{ date('Y-m-d') }}" readonly class="modern-input readonly-input">
                    </div>
                    <div class="input-group half">
                        <label class="input-label">FECHA LÍMITE</label>
                        <input type="date" name="due_date" id="input-fecha" class="modern-input" value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}">
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

                {{-- LA NUEVA SECCIÓN DE MÚLTIPLES ARCHIVOS CON IDS RESTAURADOS --}}
                <div class="input-group">
                    <label class="input-label"><i class="fas fa-paperclip"></i> ADJUNTAR EVIDENCIAS (Máx 5)</label>
                    <input type="file" name="attachments[]" id="file-upload-input" class="file-input modern-input" accept=".pdf,.doc,.docx,.jpg,.png,.jpeg" multiple>

                    <div id="archivo-actual-container" style="display: none; margin-top: 12px;">
                        <label style="font-size: 0.75rem; font-weight: bold; color: #64748b; margin-bottom: 6px; display: block;">ARCHIVOS GUARDADOS EN LA TAREA:</label>
                        <ul id="existing-files-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column;"></ul>
                    </div>

                    <div id="new-files-container" style="display: none; margin-top: 12px;">
                        <label style="font-size: 0.75rem; font-weight: bold; color: #0ea5e9; margin-bottom: 6px; display: block;">NUEVOS ARCHIVOS A SUBIR:</label>
                        <ul id="file-preview-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column;"></ul>
                    </div>
                    
                    @if($errors->any())
                        <div style="margin-top: 8px; font-size: 0.8rem; color: #d97706; background: #fef3c7; padding: 6px 10px; border-radius: 4px; border: 1px solid #fcd34d;">
                            <i class="fas fa-exclamation-triangle"></i> Por seguridad, <strong>debes volver a seleccionar tus nuevos archivos</strong>.
                        </div>
                    @endif
                    @error('attachments.*')
                        <span class="validation-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 5px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-text" onclick="cerrarModal('task-modal')">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btn-submit-tarea">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL SECUNDARIO: PANEL CRUD DE ETIQUETAS (Se mantiene igual) --}}
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
                            <input type="text" name="nombre" id="input-nombre-etiqueta" required class="modern-input" placeholder="Ej. Proyecto" value="{{ old('nombre') }}" maxlength="30" style="width: 100%; padding: 10px 45px 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; height: 42px; box-sizing: border-box;">
                        </div>
                    </div>
                    <div class="input-group" style="flex: 1; margin-bottom: 0; position: relative;">
                        <label class="input-label" style="font-size: 0.75rem; font-weight: bold; color: #4b5563; margin-bottom: 6px; display: block;">COLOR</label>
                        <input type="hidden" name="color" id="input-color-etiqueta" value="{{ old('color', '#3b82f6') }}">
                        <button type="button" id="color-picker-trigger" class="modern-input" style="display: flex; align-items: center; justify-content: space-between; height: 42px; cursor: pointer; padding: 5px 12px; background: white;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="color-picker-preview" style="width: 18px; height: 18px; border-radius: 50%; background-color: {{ old('color', '#3b82f6') }};"></span>
                                <span id="color-picker-text">{{ strtoupper(old('color', '#3B82F6')) }}</span>
                            </div>
                        </button>
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
                        <li class="tag-item-manager" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; margin-bottom: 8px; border-radius: 6px; border-left: 4px solid {{ $etiqueta->color }};">
                            <div class="tag-info-display" style="display: flex; align-items: center; gap: 10px;">
                                <div class="color-indicator" style="width: 12px; height: 12px; border-radius: 50%; background-color: {{ $etiqueta->color }};"></div>
                                <span class="tag-text" style="font-weight: 500; color: #374151;">{{ $etiqueta->name }}</span>
                            </div>
                            <div class="actions" style="display: flex; gap: 10px;">
                                <form action="{{ url('/labels') }}/{{ $etiqueta->id }}" method="POST" style="margin: 0;" onsubmit="eliminarEtiquetaAjax(event, this)">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Eliminar" style="background: none; border: none; cursor: pointer; color: #9ca3af;"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 15px; text-align: center;">No has creado ninguna etiqueta.</p>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- SCRIPTS GLOBALES DEL SISTEMA --}}
    @include('partials.scripts')

    {{-- =========================================================
         EL MOTOR DE JAVASCRIPT PARA MÚLTIPLES ARCHIVOS
         ========================================================= --}}
    <script>
        let modalDataTransfer = new DataTransfer();

        const fileInputMulti = document.getElementById('file-upload-input');
        if(fileInputMulti) {
            fileInputMulti.addEventListener('change', function(e) {
                let nuevosArchivos = this.files;
                for(let i = 0; i < nuevosArchivos.length; i++) {
                    if(modalDataTransfer.items.length >= 5) {
                        Swal.fire('Límite alcanzado', 'Solo puedes adjuntar un máximo de 5 archivos.', 'warning');
                        break;
                    }
                    modalDataTransfer.items.add(nuevosArchivos[i]);
                }
                this.files = modalDataTransfer.files;
                renderizarArchivosNuevos();
            });
        }

        function renderizarArchivosNuevos() {
            const list = document.getElementById('file-preview-list');
            const container = document.getElementById('new-files-container');
            list.innerHTML = '';

            if(modalDataTransfer.items.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            for(let i = 0; i < modalDataTransfer.files.length; i++) {
                const file = modalDataTransfer.files[i];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                
                list.innerHTML += `
                    <li class="file-preview-item">
                        <div class="file-preview-info">
                            <i class="fas fa-file-upload" style="color: #0ea5e9;"></i>
                            <span title="${file.name}">${file.name} (${sizeMB} MB)</span>
                        </div>
                        <button type="button" class="file-remove-btn" onclick="eliminarArchivoNuevo(${i})" title="Descartar"><i class="fas fa-times"></i></button>
                    </li>
                `;
            }
        }

        function eliminarArchivoNuevo(index) {
            const tempDT = new DataTransfer();
            for(let i = 0; i < modalDataTransfer.files.length; i++) {
                if(i !== index) tempDT.items.add(modalDataTransfer.files[i]);
            }
            modalDataTransfer = tempDT; 
            document.getElementById('file-upload-input').files = modalDataTransfer.files; 
            renderizarArchivosNuevos(); 
        }

        function eliminarArchivoViejo(index) {
            document.getElementById('existing-file-' + index).remove();
            if(document.querySelectorAll('.existing-file-item').length === 0) {
                document.getElementById('archivo-actual-container').style.display = 'none';
            }
        }

        document.addEventListener('click', function(e) {
            const contentClick = e.target.closest('.content');
            
            if(contentClick && contentClick.hasAttribute('data-archivos')) {
                modalDataTransfer = new DataTransfer();
                if(fileInputMulti) fileInputMulti.files = modalDataTransfer.files;
                renderizarArchivosNuevos();

                const archivosJSON = contentClick.getAttribute('data-archivos');
                const existingList = document.getElementById('existing-files-list');
                const existingContainer = document.getElementById('archivo-actual-container');
                
                if(existingList && existingContainer) {
                    existingList.innerHTML = '';
                    if(archivosJSON && archivosJSON !== 'null' && archivosJSON !== '[]' && archivosJSON !== '') {
                        const archivos = JSON.parse(archivosJSON);
                        existingContainer.style.display = 'block';
                        
                        archivos.forEach((archivo, index) => {
                            existingList.innerHTML += `
                                <li class="file-preview-item existing-file-item" id="existing-file-${index}">
                                    <div class="file-preview-info">
                                        <i class="fas fa-database" style="color: #64748b;"></i>
                                        <span>${archivo.original_name}</span>
                                        <input type="hidden" name="retained_files[]" value="${archivo.path}">
                                    </div>
                                    <button type="button" class="file-remove-btn" onclick="eliminarArchivoViejo(${index})" title="Eliminar del servidor"><i class="fas fa-trash"></i></button>
                                </li>
                            `;
                        });
                    } else {
                        existingContainer.style.display = 'none';
                    }
                }
            } else if (e.target.closest('button[onclick="abrirModalNuevaTarea()"]')) {
                modalDataTransfer = new DataTransfer();
                if(fileInputMulti) fileInputMulti.files = modalDataTransfer.files;
                renderizarArchivosNuevos();
                document.getElementById('archivo-actual-container').style.display = 'none';
                document.getElementById('existing-files-list').innerHTML = '';
            }
        });
    </script>
</body>
</html>