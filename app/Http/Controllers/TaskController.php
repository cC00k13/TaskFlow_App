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
    // Mantenemos el método create original por si lo usas fuera del modal
    public function create(): View 
    {
        return view('task.create');
    }

    public function store(Request $request): RedirectResponse 
    {
        // 1. Validamos todos los campos (Con reglas de unicidad)
        $validated = $request->validate([
            'title' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('tasks')->where('user_id', Auth::id())->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'due_date'    => ['required', 'date', Rule::date()->todayOrAfter()],
            'priority'    => 'required|string', 
            'attachment'  => 'nullable|file|max:2048',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ], [
            // Mensajes formales personalizados para la vista
            'title.required' => 'El título de la tarea no puede estar vacío ni contener solo espacios.',
            'title.unique'   => 'Ya tienes una tarea activa con este mismo título.'
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
            'title' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('tasks')->where('user_id', Auth::id())->ignore($id)->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'priority'    => 'required|string', 
            'status'      => 'required|string',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ], [
            'title.required' => 'El título de la tarea no puede estar vacío ni contener solo espacios.',
            'title.unique'   => 'Ya tienes una tarea activa con este mismo título.'
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
        // 1. Encontrar la tarea (buscamos normalmente, sin withTrashed)
        $task = Task::findOrFail($id);

        // 2. Solo el dueño puede destruirla
        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes eliminar esta tarea.');
        }

        // 3. Borrado Lógico (SoftDelete). ¡Desaparece de la vista pero se queda en la BD!
        $task->delete();

        // 4. Recargar la página con mensaje de confirmación actualizado
        return back()->with('success', '¡Tarea eliminada (enviada a la papelera)!');
    }
    
    public function updateStatusAjax(Request $request, $id)
    {
    $request->validate([
        'status' => 'required|in:pending,in_progress,completed'
    ]);

    $task = Task::findOrFail($id);
    $task->update(['status' => $request->status]);

    return response()->json(['success' => true]);
    }
}