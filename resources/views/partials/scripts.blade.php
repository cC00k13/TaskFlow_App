<script>
    const FECHA_HOY = "{{ date('Y-m-d') }}";

    // ==========================================
    // CONFIGURACIÓN GLOBAL DE SWEETALERT
    // ==========================================
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // ==========================================
    // 1. Lógica de "Borrador" y Alertas Globales
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        // Mostrar notificación de éxito moderna
        @if(session('success'))
            Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
        @endif

        // Lógica de Errores
        @if($errors->any())
            // 1. Mostrar alerta general
            Swal.fire({
                icon: 'error',
                title: '¡Hay un problema!',
                text: 'Por favor, revisa los campos marcados en rojo en el formulario.',
                confirmButtonColor: '#2563eb', // Azul TaskFlow
                backdrop: `rgba(0,0,0,0.4)`
            });

            // 2. Reabrir el modal correcto con los datos del borrador
            @if($errors->has('nombre') || $errors->has('color'))
                abrirModalEtiquetas();
            @else
                const modal = document.getElementById('task-modal');
                if (modal) {
                    modal.style.display = 'flex';
                    if("{{ old('_method') }}" === "PUT") {
                        document.getElementById('modal-titulo-principal').innerText = 'Corregir Tarea';
                        document.getElementById('btn-submit-tarea').innerText = 'Actualizar Cambios';
                    }
                }
            @endif
        @endif
    });

    // ==========================================
    // 2. Lógica de Contador de Caracteres (Etiquetas)
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
                    contador.style.color = '#ef4444';
                    contador.style.fontWeight = 'bold';
                    mensajeLimite.style.display = 'block';
                } else {
                    contador.style.color = '#9ca3af';
                    contador.style.fontWeight = 'normal';
                    mensajeLimite.style.display = 'none';
                }
            };

            inputNombre.addEventListener('input', actualizarContador);
            actualizarContador(); 
        }
    });

    // ==========================================
    // 3. Lógica de la Paleta de Colores (Menú Desplegable)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const swatches = document.querySelectorAll('.color-swatch');
        const hiddenColorInput = document.getElementById('input-color-etiqueta');
        const customColorPicker = document.getElementById('custom-color-picker');
        const customWrapper = document.querySelector('.custom-color-wrapper');
        const triggerBtn = document.getElementById('color-picker-trigger');
        const previewCircle = document.getElementById('color-picker-preview');
        const previewText = document.getElementById('color-picker-text');
        const dropdown = document.getElementById('color-picker-dropdown');

        if(triggerBtn) {
            triggerBtn.addEventListener('click', function(e) {
                e.preventDefault(); 
                dropdown.classList.toggle('hide');
                triggerBtn.classList.toggle('is-open');
            });
        }

        document.addEventListener('click', function(e) {
            if (triggerBtn && dropdown && !triggerBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hide');
                triggerBtn.classList.remove('is-open');
            }
        });

        function selectColor(colorHex, isCustom = false) {
            if(!hiddenColorInput) return;
            hiddenColorInput.value = colorHex; 

            if(previewCircle) previewCircle.style.backgroundColor = colorHex;
            if(previewText) previewText.innerText = colorHex.toUpperCase();

            swatches.forEach(s => s.classList.remove('selected'));
            if(customWrapper) customWrapper.classList.remove('selected');

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
            
            if(!isCustom && dropdown) {
                dropdown.classList.add('hide');
                if(triggerBtn) triggerBtn.classList.remove('is-open');
            }
        }

        swatches.forEach(swatch => {
            swatch.addEventListener('click', function() {
                selectColor(this.getAttribute('data-color'));
            });
        });

        if(customColorPicker) {
            customColorPicker.addEventListener('input', function() {
                selectColor(this.value, true);
            });
        }

        window.setEtiquetaColorUI = selectColor;
    });

    // ==========================================
    // 4. Lógica Drag and Drop (Kanban)
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

                if(contentDiv) contentDiv.setAttribute('data-estado', newStatus);

                if (newStatus === 'completed') {
                    itemEl.classList.add('completed');
                    itemEl.style.borderLeftColor = ''; 
                    if(checkbox) { checkbox.checked = true; checkbox.title = "Devolver a pendientes"; }
                    if(hiddenInput) hiddenInput.value = 'pending'; 
                } else {
                    itemEl.classList.remove('completed');
                    itemEl.style.borderLeftColor = (newStatus === 'in_progress') ? '#0284c7' : ''; 
                    if(checkbox) { checkbox.checked = false; checkbox.title = "Marcar como completada"; }
                    if(hiddenInput) hiddenInput.value = 'completed';
                }

                if (evt.from !== toList) {
                    fetch(`/tareas/${taskId}/estado-ajax`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ status: newStatus })
                    }).catch(error => console.error('Error:', error));
                }
            }
        };

        ['list-pending', 'list-in_progress', 'list-completed'].forEach(id => {
            const el = document.getElementById(id);
            if(el) new Sortable(el, sortableOptions);
        });
    });

    // ==========================================
    // 5. Funciones de Modales (Limpieza Forzada)
    // ==========================================
    function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function abrirModalEtiquetas() { 
        if(window.setEtiquetaColorUI && !document.getElementById('input-color-etiqueta').value) {
             window.setEtiquetaColorUI('#3b82f6');
        }
        document.getElementById('label-modal').style.display = 'flex'; 
    }

    function editarEtiqueta(id, nombre, color) {
        document.getElementById('modal-titulo-etiqueta').innerText = 'Editar Etiqueta';
        document.getElementById('form-etiqueta').action = '/labels/' + id;
        document.getElementById('metodo-etiqueta').value = 'PUT';
        document.getElementById('input-nombre-etiqueta').value = nombre;
        if(window.setEtiquetaColorUI) window.setEtiquetaColorUI(color);
        document.getElementById('btn-submit-etiqueta').innerText = 'Guardar Cambios';
        document.getElementById('btn-cancelar-etiqueta').classList.remove('hide');
        document.getElementById('input-nombre-etiqueta').focus();
        document.getElementById('input-nombre-etiqueta').dispatchEvent(new Event('input'));
    }

    function resetearFormularioEtiquetas() {
        const form = document.getElementById('form-etiqueta');
        document.getElementById('modal-titulo-etiqueta').innerText = 'Mis Etiquetas';
        form.action = '{{ url('/label/create') }}';
        document.getElementById('metodo-etiqueta').value = 'POST';
        
        const inputName = document.getElementById('input-nombre-etiqueta');
        if(inputName) {
            inputName.value = ''; 
            inputName.classList.remove('is-invalid');
            inputName.dispatchEvent(new Event('input')); 
        }

        if(window.setEtiquetaColorUI) window.setEtiquetaColorUI('#3b82f6'); 

        document.getElementById('btn-submit-etiqueta').innerText = 'Crear Nueva';
        document.getElementById('btn-cancelar-etiqueta').classList.add('hide');
        
        document.querySelectorAll('#form-etiqueta .validation-error, #form-etiqueta .alert-backend').forEach(el => el.style.display = 'none');
    }

    function abrirModalNuevaTarea() {
        const form = document.getElementById('form-tarea');
        document.getElementById('modal-titulo-principal').innerText = 'Nueva Tarea';
        form.action = '{{ url('/task/create') }}';
        document.getElementById('metodo-formulario').value = 'POST';
        
        document.getElementById('input-titulo').value = '';
        document.getElementById('input-descripcion').value = '';
        document.getElementById('input-fecha').value = '';
        document.getElementById('input-prioridad').value = 'medium'; 
        document.getElementById('input-estado').value = 'pending'; 
        
        const fileInput = form.querySelector('input[type="file"]');
        if(fileInput) fileInput.value = ''; 

        document.getElementById('btn-submit-tarea').innerText = 'Guardar Tarea';
        document.getElementById('input-fecha').setAttribute('min', FECHA_HOY);
        document.getElementById('archivo-actual-container').style.display = 'none';

        document.querySelectorAll('.label-checkbox-input').forEach(cb => cb.checked = false);
        
        document.querySelectorAll('#form-tarea .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('#form-tarea .validation-error').forEach(el => el.style.display = 'none');
        
        const avisoArchivo = form.querySelector('.fa-exclamation-triangle')?.parentElement;
        if(avisoArchivo) avisoArchivo.style.display = 'none';

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
        
        let nombreArchivo = elemento.getAttribute('data-archivo');
        document.getElementById('archivo-actual-container').style.display = nombreArchivo ? 'block' : 'none';
        if(nombreArchivo) document.getElementById('nombre-archivo-actual').innerText = nombreArchivo;

        let etiquetas = JSON.parse(elemento.getAttribute('data-etiquetas') || '[]');
        document.querySelectorAll('.label-checkbox-input').forEach(checkbox => {
            checkbox.checked = etiquetas.includes(parseInt(checkbox.value));
        });

        document.querySelectorAll('#form-tarea .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('#form-tarea .validation-error').forEach(el => el.style.display = 'none');
        const avisoArchivo = document.getElementById('form-tarea').querySelector('.fa-exclamation-triangle')?.parentElement;
        if(avisoArchivo) avisoArchivo.style.display = 'none';

        document.getElementById('btn-submit-tarea').innerText = 'Actualizar Cambios';
        document.getElementById('task-modal').style.display = 'flex';
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            event.target.style.display = 'none';
        }
    }

    // ==========================================
    // 6. Prevención de envíos múltiples (Spam de clics)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Ignorar formularios manejados por SweetAlert
                if (this.getAttribute('onsubmit') && this.getAttribute('onsubmit').includes('confirmarEliminacion')) {
                    return; 
                }
                
                // Buscar el botón principal de guardar (ignorando botones de ícono como los de borrar)
                const btn = this.querySelector('button[type="submit"]:not(.btn-icon)');
                if(btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.7';
                }
            });
        });
    });

    // ==========================================
    // 7. Alertas Modernas (SweetAlert2)
    // ==========================================
    function confirmarEliminacion(event, formulario, tipo) {
        event.preventDefault(); // Evitar envío automático

        let titulo = tipo === 'etiqueta' ? '¿Eliminar etiqueta?' : '¿Eliminar tarea?';
        let texto = tipo === 'etiqueta' 
            ? 'Se desvinculará de todas las tareas. Esta acción no se puede deshacer.' 
            : 'Esta tarea será enviada a la papelera.';

        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', // Rojo peligro
            cancelButtonColor: '#6b7280',  // Gris neutro
            confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            backdrop: `rgba(0,0,0,0.4)`
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                formulario.submit();
            }
        });
    }
</script>