<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\Task;
use App\Models\Label; // Agregado para que no marque error en el index
use Illuminate\Support\Facades\Auth; 

class TaskController extends Controller
{
    public function index(Request $request) // Se añade Request para escuchar los filtros del usuario
    {
        // 1. Iniciamos la consulta base (solo tareas de este usuario)
        $query = Task::with('labels')->where('user_id', Auth::id());

        // 2. Escuchamos la petición de ordenamiento que viene del frontend
        $orden = $request->input('ordenar_por', 'fecha_proxima'); // Por defecto ordena por la más próxima

        switch ($orden) {
            case 'mas_recientes':
                $query->orderBy('created_at', 'desc');
                break;
            case 'prioridad_alta':
                // Nota: Asumiendo que high > medium > low. Puede requerir ajuste según cómo guarden los datos.
                $query->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END");
                break;
            case 'fecha_proxima':
            default:
                $query->orderBy('due_date', 'asc');
                break;
        }

        // 3. Ejecutamos la consulta para traer las tareas ya ordenadas
        $tasks = $query->get();

        // 4. Traemos las etiquetas que el usuario ha creado
        $labels = Label::where('user_id', Auth::id())->get();

        // 5. Mandamos las variables a la vista
        return view('dashboard', compact('tasks', 'labels')); 
    }

    // Mantenemos el método create original por si lo usas fuera del modal
    public function create(): View 
    {
        return view('task.create');
    }

    public function store(Request $request): RedirectResponse 
    {
        // 1. Validamos todos los campos (Quitamos 'status' porque el backend lo forzará)
        $validated = $request->validate([
            'title'       => 'required|max:255',
            'description' => 'nullable|string',
            'due_date'    => ['required', 'date', Rule::date()->todayOrAfter()],
            'priority'    => 'required|string', 
            'attachment'  => 'nullable|file|max:2048',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ]);

        // 2. Manejo de archivos adjuntos
        $rutaArchivo = null;
        if ($request->hasFile('attachment')) {
            $rutaArchivo = $request->file('attachment')->store('attachments', 'public');
        }

        // 3. Crear la Tarea inyectando el ID del usuario de forma segura y FORZANDO el estado
        $task = Task::create([
            'user_id'     => Auth::id(),
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => 'pending', // ¡EL CANDADO DE SEGURIDAD! Siempre nace pendiente
            'attachment'  => $rutaArchivo,
        ]);

        // 4. Conectar las Etiquetas (Muchos a Muchos)
        if (!empty($validated['labels'])) {
            $task->labels()->attach($validated['labels']);
        }

        // 5. Redireccionar con éxito
        return redirect('/dashboard')->with('success', '¡Tarea creada exitosamente!');
    }

    public function toggleStatus(Request $request, $id)
    {
        // 1. Encontrar la tarea específica en la base de datos
        $task = Task::findOrFail($id);

        // 2. Seguridad: Asegurar que el alumno solo modifique SUS propias tareas
        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. Esta tarea no te pertenece.');
        }

        // 3. Leemos el 'status' exacto que nos mandó el checkbox del frontend ('completed' o 'pending')
        $task->status = $request->input('status');
        
        // 4. Guardar permanentemente para no perder el progreso
        $task->save();

        // 5. Regresar al Dashboard silenciosamente
        return back()->with('success', '¡Estado de la tarea actualizado!');
    }

    public function update(Request $request, $id)
    {
        // 1. Buscar la tarea original
        $task = Task::findOrFail($id);

        // 2. Seguridad: Evitar que modifiquen tareas de otros alumnos
        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes editar esta tarea.');
        }

        // 3. Validar los datos que vienen del formulario del modal
        $validated = $request->validate([
            'title'       => 'required|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'priority'    => 'required|string', 
            'status'      => 'required|string',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ]);

        // 4. Sobreescribir los datos escalares en la base de datos
        $task->update([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => $validated['status'],
        ]);

        // 5. Sincronizar las etiquetas (La magia de sync)
        if ($request->has('labels')) {
            $task->labels()->sync($validated['labels']);
        } else {
            // Si el usuario desmarcó todas las etiquetas, vaciamos la relación
            $task->labels()->detach(); 
        }

        // 6. Regresar al panel con éxito
        return redirect('/dashboard')->with('success', '¡Tarea actualizada correctamente!');
    }

    public function destroy($id)
    {
        // 1. Encontrar la tarea (incluso si estuviera "oculta" por SoftDeletes)
        $task = Task::withTrashed()->findOrFail($id);

        // 2. Solo el dueño puede destruirla
        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes eliminar esta tarea.');
        }

        // 3. Remover permanentemente y liberar espacio en BD
        $task->forceDelete();

        // 4. Recargar la página con mensaje de confirmación
        return back()->with('success', '¡Tarea eliminada permanentemente!');
    }
}