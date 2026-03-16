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
    
    {{-- Notificación de Éxito o Error --}}
    @if(session('success'))
    <div id="toast-exito" class="toast-notification">
        <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.2rem;"></i> {{ session('success') }}
    </div>
    <script>setTimeout(() => { let t = document.getElementById('toast-exito'); if(t) { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); } }, 3500);</script>
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
                    
                    {{-- Botón para abrir el nuevo gestor de etiquetas --}}
                    <button class="btn-secondary" onclick="abrirModalEtiquetas()"><i class="fas fa-tags"></i> Mis Etiquetas</button>
                    
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="logout-icon" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></button>
                    </form>
                </div>
            </header>

            <section class="controls">
                <div class="search-bar">
                    <input type="text" placeholder="Nueva tarea rápida...">
                    <button class="btn-add" onclick="abrirModalNuevaTarea()"><i class="fas fa-plus"></i></button>
                </div>
                <div class="filter-bar">
                    <label><i class="fas fa-sort-amount-down"></i> Ordenar por:</label>
                    <select class="select-ordenar">
                        <option selected>Más próximas a vencer</option>
                        <option>Más recientes</option>
                        <option>Pendientes primero</option>
                    </select>
                </div>
            </section>

            <section class="task-container">
                <h3>Mis Tareas</h3>
                <ul class="task-list">
                    @forelse($tareas ?? [] as $tarea)
                        <li class="task-item {{ $tarea->estado == 'completada' ? 'completed' : '' }}">
                            
                            {{-- Completar Tarea Rápido --}}
                            <form action="/tareas/{{ $tarea->id ?? 0 }}/estado" method="POST" style="margin-top: 3px;">
                                @csrf @method('PATCH') 
                                <input type="hidden" name="estado" value="{{ $tarea->estado == 'completada' ? 'pendiente' : 'completada' }}">
                                <input type="checkbox" class="task-check" onchange="this.form.submit()" {{ $tarea->estado == 'completada' ? 'checked' : '' }}>
                            </form>

                            <div class="content" onclick="abrirModalEditar(this)" 
                                 data-id="{{ $tarea->id ?? '' }}" data-titulo="{{ $tarea->titulo ?? '' }}" 
                                 data-descripcion="{{ $tarea->descripcion ?? '' }}" data-fecha_limite="{{ $tarea->fecha_limite ?? '' }}" 
                                 data-prioridad="{{ $tarea->prioridad ?? 'media' }}" data-estado="{{ $tarea->estado ?? 'pendiente' }}"
                                 data-etiquetas="{{ json_encode(isset($tarea->etiquetas) ? $tarea->etiquetas->pluck('id') : []) }}">
                                <span class="title">{{ $tarea->titulo }}</span>
                                
                                <div class="tags">
                                    <span class="tag priority-{{ strtolower($tarea->prioridad ?? 'media') }}">{{ strtoupper($tarea->prioridad ?? 'MEDIA') }}</span>
                                    <span class="tag status-{{ strtolower($tarea->estado ?? 'pendiente') }}">{{ strtoupper($tarea->estado ?? 'PENDIENTE') }}</span>
                                    
                                    {{-- Etiquetas Visuales --}}
                                    @foreach($tarea->etiquetas ?? [] as $etiqueta)
                                        <span class="tag" style="background-color: {{ $etiqueta->color ?? '#eee' }}; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">{{ $etiqueta->nombre }}</span>
                                    @endforeach
                                </div>

                                <div class="task-meta">
                                    <span><i class="far fa-calendar-alt"></i> Creada: {{ $tarea->fecha_asignacion ?? 'Hoy' }}</span>
                                    @if(!empty($tarea->fecha_limite))
                                        <span style="color: #d32f2f;"><i class="far fa-clock"></i> Límite: {{ $tarea->fecha_limite }}</span>
                                    @endif
                                    @if(!empty($tarea->documento))
                                        <span><i class="fas fa-paperclip"></i> Adjunto</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="actions" style="display: flex; align-items: center;">
                                <button class="edit" onclick="abrirModalEditar(this.previousElementSibling)" title="Editar"><i class="fas fa-pen"></i></button>
                                
                                {{-- Eliminar con Alerta --}}
                                <form action="/tareas/{{ $tarea->id ?? 0 }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta tarea permanentemente?');" style="margin: 0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="task-item empty-state">
                            <div class="empty-state-content">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Aún no tienes tareas pendientes. ¡Estás al día!</span>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    {{-- MODAL PRINCIPAL: TAREAS (Crear y Editar) --}}
    <div class="modal-overlay" id="task-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="modal-titulo-principal">Detalles de la Tarea</h2>
                <button class="close-modal" onclick="cerrarModal('task-modal')"><i class="fas fa-times"></i></button>
            </div>
            
            <form id="form-tarea" action="/tareas" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="metodo-formulario" value="POST">
                
                <div class="input-group">
                    <input type="text" name="titulo" id="input-titulo" required placeholder="TÍTULO DE LA TAREA">
                </div>
                <div class="input-group">
                    <textarea name="descripcion" id="input-descripcion" rows="3" placeholder="Descripción de la tarea..."></textarea>
                </div>

                {{-- Selector Múltiple de Etiquetas --}}
                <div class="input-group">
                    <label class="select-label"><i class="fas fa-tags"></i> ETIQUETAS (Deja presionado Ctrl para seleccionar varias)</label>
                    <select name="etiquetas[]" id="input-etiquetas" multiple class="custom-select select-multiple">
                        @foreach($etiquetas_usuario ?? [] as $etiqueta)
                            <option value="{{ $etiqueta->id }}">{{ $etiqueta->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-row">
                    <div class="input-group half">
                        <label class="select-label">FECHA DE ASIGNACIÓN</label>
                        <input type="date" name="fecha_asignacion" value="{{ date('Y-m-d') }}" readonly class="custom-select readonly-input">
                    </div>
                    <div class="input-group half">
                        <label class="select-label">FECHA LÍMITE</label>
                        <input type="date" name="fecha_limite" id="input-fecha" class="custom-select">
                    </div>
                </div>

                <div class="modal-row">
                    <div class="input-group half">
                        <label class="select-label">PRIORIDAD</label>
                        <select name="prioridad" id="input-prioridad" class="custom-select">
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta" selected>Alta</option>
                        </select>
                    </div>
                    <div class="input-group half">
                        <label class="select-label">ESTADO</label>
                        <select name="estado" id="input-estado" class="custom-select">
                            <option value="pendiente" selected>Pendiente</option>
                            <option value="progreso" >En Progreso</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                </div>

                <div class="input-group" style="margin-top: 10px;">
                    <label class="select-label"><i class="fas fa-paperclip"></i> ADJUNTAR DOCUMENTO</label>
                    <input type="file" name="documento" class="custom-select" accept=".pdf,.doc,.docx,.jpg,.png" style="padding-top: 15px;">
                </div>

                <div class="form-actions" style="margin-top: 25px;">
                    <button type="button" class="btn-cancel" onclick="cerrarModal('task-modal')">Cancelar</button>
                    <button type="submit" class="btn-submit" id="btn-submit-tarea">Guardar Tarea</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL SECUNDARIO: GESTIONAR ETIQUETAS --}}
    <div class="modal-overlay" id="label-modal">
        <div class="modal-card" style="max-width: 450px;">
            <div class="modal-header">
                <h2>Mis Etiquetas</h2>
                <button class="close-modal" onclick="cerrarModal('label-modal')"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="/etiquetas" method="POST" style="background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                @csrf
                <div class="modal-row" style="align-items: flex-end;">
                    <div class="input-group half" style="margin-bottom: 0;">
                        <label class="select-label">NOMBRE</label>
                        <input type="text" name="nombre" required placeholder="Ej. Examen" class="custom-select">
                    </div>
                    <div class="input-group half" style="margin-bottom: 0;">
                        <label class="select-label">COLOR</label>
                        <input type="color" name="color" value="#ff2a5f" class="color-picker">
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-full">Crear Etiqueta</button>
            </form>

            <label class="select-label">ETIQUETAS ACTUALES</label>
            <ul class="etiqueta-list">
                @forelse($etiquetas_usuario ?? [] as $etiqueta)
                    <li class="etiqueta-item" style="border-left-color: {{ $etiqueta->color }};">
                        <strong>{{ $etiqueta->nombre }}</strong>
                        <form action="/etiquetas/{{ $etiqueta->id }}" method="POST" onsubmit="return confirm('¿Borrar etiqueta permanentemente?');" style="margin: 0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete-tag"><i class="fas fa-trash"></i></button>
                        </form>
                    </li>
                @empty
                    <p class="empty-tag-msg">Aún no has creado categorías.</p>
                @endforelse
            </ul>
        </div>
    </div>

    <script>
        function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
        function abrirModalEtiquetas() { document.getElementById('label-modal').style.display = 'flex'; }
        
        function abrirModalNuevaTarea() {
            document.getElementById('modal-titulo-principal').innerText = 'Nueva Tarea';
            document.getElementById('form-tarea').action = '/tareas';
            document.getElementById('metodo-formulario').value = 'POST';
            document.getElementById('form-tarea').reset();
            document.getElementById('btn-submit-tarea').innerText = 'Guardar Tarea';
            
            let selectEtiquetas = document.getElementById('input-etiquetas');
            for(let i=0; i < selectEtiquetas.options.length; i++){ selectEtiquetas.options[i].selected = false; }
            document.getElementById('task-modal').style.display = 'flex';
        }

        function abrirModalEditar(elemento) {
            document.getElementById('modal-titulo-principal').innerText = 'Editar Tarea';
            let id = elemento.getAttribute('data-id');
            document.getElementById('form-tarea').action = '/tareas/' + id;
            document.getElementById('metodo-formulario').value = 'PUT';
            
            document.getElementById('input-titulo').value = elemento.getAttribute('data-titulo');
            document.getElementById('input-descripcion').value = elemento.getAttribute('data-descripcion');
            document.getElementById('input-fecha').value = elemento.getAttribute('data-fecha_limite');
            document.getElementById('input-prioridad').value = elemento.getAttribute('data-prioridad');
            document.getElementById('input-estado').value = elemento.getAttribute('data-estado');
            
            let etiquetasAsignadas = JSON.parse(elemento.getAttribute('data-etiquetas') || '[]');
            let selectEtiquetas = document.getElementById('input-etiquetas');
            for(let i=0; i < selectEtiquetas.options.length; i++){
                selectEtiquetas.options[i].selected = etiquetasAsignadas.includes(parseInt(selectEtiquetas.options[i].value));
            }

            document.getElementById('btn-submit-tarea').innerText = 'Actualizar Tarea';
            document.getElementById('task-modal').style.display = 'flex';
        }
    </script>
</body>
</html>