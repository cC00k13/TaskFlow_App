<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\Task;
use Illuminate\Support\Facades\Auth; 

class TaskController extends Controller
{
    public function index()
    {
        // Traemos las tareas (con sus etiquetas para evitar lentitud)
        $tasks = Task::with('labels')
                     ->where('user_id', Auth::id())
                     ->orderBy('due_date', 'asc')
                     ->get();

        // Traemos las etiquetas que el usuario ha creado
        $labels = \App\Models\Label::where('user_id', Auth::id())->get();

        // Mandamos las variables en inglés a la vista
        return view('dashboard', compact('tasks', 'labels')); 
    }

    // Mantenemos tu método create original por si lo usas fuera del modal
    public function create(): View 
    {
        return view('task.create');
    }

    public function store(Request $request): RedirectResponse 
    {
        // 1. Validamos todos los campos de tu modal y arreglamos la regla de fecha
        $validated = $request->validate([
            'title'       => 'required|max:255',
            'description' => 'nullable|string',
            'due_date'    => ['required', 'date', Rule::date()->todayOrAfter()], // Tu regla adaptada al nuevo nombre
            'priority'    => 'required|string', 
            'status'      => 'required|string',
            'attachment'  => 'nullable|file|max:2048',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ]);

        // 2. Manejo de archivos adjuntos
        $rutaArchivo = null;
        if ($request->hasFile('attachment')) {
            $rutaArchivo = $request->file('attachment')->store('attachments', 'public');
        }

        // 3. Crear la Tarea inyectando el ID del usuario de forma segura por backend
        $task = Task::create([
            'user_id'     => Auth::id(),
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => $validated['status'],
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

        // 3. Traducir lo que manda el formulario (español) a la base de datos (inglés)
        $estadoRecibido = $request->input('estado'); // Viene como 'completada' o 'pendiente'
        $estadoParaDb = ($estadoRecibido === 'completada') ? 'completed' : 'pending';

        // 4. Guardar permanentemente para no perder el progreso
        $task->status = $estadoParaDb;
        $task->save();

        // 5. Regresar al Dashboard silenciosamente
        return back()->with('success', '¡Progreso guardado!');
    }
}
