<script>
    const FECHA_HOY = "{{ date('Y-m-d') }}";

    // ==========================================
    // 1. Lógica de Contador de Caracteres (Etiquetas)
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
    // 2. Lógica de la Paleta de Colores (Menú Desplegable)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const swatches = document.querySelectorAll('.color-swatch');
        const hiddenColorInput = document.getElementById('input-color-etiqueta');
        const customColorPicker = document.getElementById('custom-color-picker');
        const customWrapper = document.querySelector('.custom-color-wrapper');
        
        // Elementos del nuevo Dropdown
        const triggerBtn = document.getElementById('color-picker-trigger');
        const previewCircle = document.getElementById('color-picker-preview');
        const previewText = document.getElementById('color-picker-text');
        const dropdown = document.getElementById('color-picker-dropdown');

        // Abrir/Cerrar menú al hacer clic en el botón
        if(triggerBtn) {
            triggerBtn.addEventListener('click', function(e) {
                e.preventDefault(); 
                dropdown.classList.toggle('hide');
                triggerBtn.classList.toggle('is-open'); // <-- Añadido para girar la flecha
            });
        }

        // Cerrar menú si el usuario hace clic afuera
        document.addEventListener('click', function(e) {
            if (triggerBtn && dropdown && !triggerBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hide');
                triggerBtn.classList.remove('is-open'); // <-- Restaura la flecha
            }
        });

        function selectColor(colorHex, isCustom = false) {
            if(!hiddenColorInput) return;
            
            hiddenColorInput.value = colorHex; 

            // Actualizar la vista del botón principal
            if(previewCircle) previewCircle.style.backgroundColor = colorHex;
            if(previewText) previewText.innerText = colorHex.toUpperCase();

            // Limpiar selección visual en la paleta
            swatches.forEach(s => s.classList.remove('selected'));
            if(customWrapper) customWrapper.classList.remove('selected');

            // Marcar visualmente el nuevo color
            if (!isCustom) {
                const swatchToSelect = Array.from(swatches).find(s => s.getAttribute('data-color') === colorHex);
                if (swatchToSelect) {
                    swatchToSelect.classList.add('selected');
                } else {
                    if(customWrapper) customWrapper.classList.add('selected');
                    if(customColorPicker) customColorPicker.value = colorHex;
                }
            } else {
                if(customWrapper) customWrapper.classList.add('selected');
            }
            
            // Cerrar el menú automáticamente al elegir un color
            if(!isCustom && dropdown) {
                dropdown.classList.add('hide');
                if(triggerBtn) triggerBtn.classList.remove('is-open'); // <-- Restaura flecha
            }
        }

        // Clics en los colores predefinidos
        swatches.forEach(swatch => {
            swatch.addEventListener('click', function() {
                selectColor(this.getAttribute('data-color'));
            });
        });

        // Cambios en el color personalizado (Arcoíris)
        if(customColorPicker) {
            customColorPicker.addEventListener('input', function() {
                selectColor(this.value, true);
            });
        }

        // Guardar función globalmente para poder usarla al Editar/Resetear
        window.setEtiquetaColorUI = selectColor;
    });

    // ==========================================
    // 3. Lógica Drag and Drop (Arrastrar Kanban)
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

                // Ordenamiento Automático Cronológico
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
    // 4. Funciones de Modales Originales
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
    
    @if($errors->has('title') || $errors->has('attachment'))
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('task-modal').style.display = 'flex';
        });
    @endif
    
    function editarEtiqueta(id, nombre, color) {
        document.getElementById('modal-titulo-etiqueta').innerText = 'Editar Etiqueta';
        document.getElementById('form-etiqueta').action = '/labels/' + id;
        document.getElementById('metodo-etiqueta').value = 'PUT';
        document.getElementById('input-nombre-etiqueta').value = nombre;
        
        // ACTUALIZADO: Pintar la paleta de colores
        if(window.setEtiquetaColorUI) {
            window.setEtiquetaColorUI(color);
        } else {
            const inputHidden = document.getElementById('input-color-etiqueta');
            if(inputHidden) inputHidden.value = color;
        }

        document.getElementById('btn-submit-etiqueta').innerText = 'Guardar Cambios';
        document.getElementById('btn-cancelar-etiqueta').classList.remove('hide');
        document.getElementById('input-nombre-etiqueta').focus();
        
        // Forzar disparo del contador al editar
        const inputName = document.getElementById('input-nombre-etiqueta');
        if(inputName) inputName.dispatchEvent(new Event('input'));
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

        // ACTUALIZADO: Resetear color a azul por defecto en la paleta
        if(window.setEtiquetaColorUI) window.setEtiquetaColorUI('#3b82f6');

        document.getElementById('btn-submit-etiqueta').innerText = 'Crear Nueva';
        document.getElementById('btn-cancelar-etiqueta').classList.add('hide');
        
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

        // Ocultar mensaje de archivo cargado
        document.getElementById('archivo-actual-container').style.display = 'none';

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
        
        // Lógica Archivo Visual
        let nombreArchivo = elemento.getAttribute('data-archivo');
        const archivoContainer = document.getElementById('archivo-actual-container');
        if(nombreArchivo) {
            document.getElementById('nombre-archivo-actual').innerText = nombreArchivo;
            archivoContainer.style.display = 'block';
        } else {
            archivoContainer.style.display = 'none';
        }

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