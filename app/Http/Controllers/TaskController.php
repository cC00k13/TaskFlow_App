<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\Task;
use App\Models\Label;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Storage; // Necesario para borrar y descargar archivos

class TaskController extends Controller
{
    // Mantenemos el método create original por si lo usas fuera del modal
    public function create(): View 
    {
        return view('task.create');
    }

    public function store(Request $request): RedirectResponse 
    {
        // 1. Validamos todos los campos
        $validated = $request->validate([
            'title' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('tasks')->where('user_id', Auth::id())->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'due_date'    => ['required', 'date', Rule::date()->todayOrAfter()],
            'priority'    => 'required|string|in:low,medium,high', 
            'attachment'  => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:5120', // Máx 5MB
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ], [
            'title.required' => 'El título de la tarea no puede estar vacío.',
            'title.unique'   => 'Ya tienes una tarea activa con este mismo título.',
            'attachment.mimes' => 'El archivo debe ser PDF, Word o Imagen.',
            'attachment.max' => 'El archivo no debe pesar más de 5MB.'
        ]);

        // 2. Manejo de archivos adjuntos (Storage de Laravel)
        $rutaArchivo = null;
        if ($request->hasFile('attachment')) {
            // Guarda el archivo en storage/app/public/attachments
            $rutaArchivo = $request->file('attachment')->store('attachments', 'public');
        }

        // 3. Crear la Tarea
        $task = Task::create([
            'user_id'     => Auth::id(),
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => 'pending', // Siempre nace pendiente
            'attachment'  => $rutaArchivo, // Guardamos la ruta del archivo
        ]);

        // 4. Conectar las Etiquetas (Muchos a Muchos)
        if (!empty($validated['labels'])) {
            $task->labels()->attach($validated['labels']);
        }

        return redirect('/dashboard')->with('success', '¡Tarea creada exitosamente!');
    }

    public function toggleStatus(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. Esta tarea no te pertenece.');
        }

        $task->status = $request->input('status');
        $task->save();

        return back()->with('success', '¡Estado de la tarea actualizado!');
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes editar esta tarea.');
        }

        $validated = $request->validate([
            'title' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('tasks')->where('user_id', Auth::id())->ignore($id)->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'priority'    => 'required|string|in:low,medium,high', 
            'status'      => 'required|string|in:pending,in_progress,completed',
            'attachment'  => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:5120',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ], [
            'title.required' => 'El título de la tarea no puede estar vacío.',
            'title.unique'   => 'Ya tienes una tarea activa con este mismo título.'
        ]);

        // 4. Actualización del archivo adjunto (Si el usuario sube uno nuevo)
        if ($request->hasFile('attachment')) {
            // Si ya tenía un archivo viejo, lo borramos para no ocupar espacio basura
            if ($task->attachment) {
                Storage::disk('public')->delete($task->attachment);
            }
            // Guardamos el nuevo archivo
            $task->attachment = $request->file('attachment')->store('attachments', 'public');
        }

        // 5. Sobreescribir los datos en la base de datos
        $task->update([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => $validated['status'],
        ]);

        // 6. Sincronizar las etiquetas
        if ($request->has('labels')) {
            $task->labels()->sync($validated['labels']);
        } else {
            $task->labels()->detach(); 
        }

        return redirect('/dashboard')->with('success', '¡Tarea actualizada correctamente!');
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes eliminar esta tarea.');
        }

        // Nota: Como usamos SoftDeletes, NO borramos el archivo físico de Storage todavía
        // por si el usuario quiere "recuperar" la tarea de la papelera en el futuro.
        $task->delete();

        return back()->with('success', '¡Tarea eliminada (enviada a la papelera)!');
    }
    
    public function updateStatusAjax(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $task = Task::findOrFail($id);
        
        // Seguridad para AJAX
        if ($task->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $task->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    // ==========================================
    // NUEVA FUNCIÓN: Descargar/Ver Evidencia
    // ==========================================
    public function downloadAttachment($id)
    {
        $task = Task::findOrFail($id);

        // Seguridad: Solo el dueño de la tarea puede ver/descargar el archivo
        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes ver este archivo.');
        }

        if (!$task->attachment) {
            return back()->withErrors(['attachment' => 'Esta tarea no tiene ninguna evidencia adjunta.']);
        }

        // Construir la ruta completa al archivo en la carpeta storage/app/public/attachments
        $rutaArchivo = storage_path('app/public/' . $task->attachment);
        
        // Verificar que el archivo exista físicamente en el servidor
        if (file_exists($rutaArchivo)) {
            // El método response()->file() abre el archivo en el navegador (útil para PDFs o imágenes).
            // Si quisieras forzar la descarga, usarías response()->download($rutaArchivo);
            return response()->file($rutaArchivo);
        }

        return back()->withErrors(['attachment' => 'El archivo no se encontró en el servidor.']);
    }
}