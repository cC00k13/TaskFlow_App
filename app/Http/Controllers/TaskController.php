<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\Task;
use App\Models\Label;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Storage; 

class TaskController extends Controller
{
    // Mantenemos el método create original por si lo usas fuera del modal
    public function create(): View 
    {
        return view('task.create');
    }

   public function store(Request $request): RedirectResponse 
    {
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
            // Validamos que sea un arreglo de archivos y cada uno cumpla las reglas
            'attachments'   => 'nullable|array|max:5', // Máximo 5 archivos por tarea
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,png,jpeg|max:5120',
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ], [
            'title.required' => 'El título de la tarea no puede estar vacío.',
            'title.unique'   => 'Ya tienes una tarea activa con este mismo título.',
            'attachments.*.mimes' => 'Uno de los archivos no es válido. Solo se permite PDF, Word o Imagen.',
            'attachments.*.max' => 'Ningún archivo debe pesar más de 5MB.'
        ]);

        // Construir la estructura JSON para las evidencias
        $archivosData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Guardamos en la bóveda privada (blindaje intacto)
                $path = $file->store('attachments', 'local');
                
                // Extraemos los metadatos para la UI interactiva
                $archivosData[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(), // En bytes, lo formatearemos en el frontend
                ];
            }
        }

        $task = Task::create([
            'user_id'     => Auth::id(),
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => 'pending',
            'attachments' => empty($archivosData) ? null : $archivosData, // Inyectamos el JSON
        ]);

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

        // Evitamos back(), redirigimos explícitamente al dashboard
        return redirect('/dashboard')->with('success', '¡Estado de la tarea actualizado!');
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes editar esta tarea.');
        }

        $validated = $request->validate([
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('tasks')->where('user_id', Auth::id())->ignore($id)->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'priority'    => 'required|string|in:low,medium,high', 
            'status'      => 'required|string|in:pending,in_progress,completed',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,png,jpeg|max:5120',
            'retained_files' => 'nullable|array', // Archivos viejos que el usuario NO borró
            'labels'      => 'nullable|array',
            'labels.*'    => 'exists:labels,id'
        ]);

        // 1. Recuperar los archivos viejos que el usuario decidió conservar
        $archivosFinales = [];
        $archivosActuales = $task->attachments ?? [];
        $rutasConservadas = $request->input('retained_files', []);

        foreach ($archivosActuales as $archivoViejo) {
            if (in_array($archivoViejo['path'], $rutasConservadas)) {
                $archivosFinales[] = $archivoViejo; // Lo mantenemos
            } else {
                // Si no está en conservados, lo borramos físicamente del servidor
                Storage::disk('local')->delete($archivoViejo['path']);
            }
        }

        // 2. Procesar los archivos nuevos (si subió más)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'local');
                $archivosFinales[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                ];
            }
        }

        $task->update([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'due_date'    => $validated['due_date'],
            'priority'    => $validated['priority'],
            'status'      => $validated['status'],
            'attachments' => empty($archivosFinales) ? null : $archivosFinales,
        ]);

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

        $task->delete();

        // Evitamos back(), redirigimos explícitamente al dashboard para evitar "Página no encontrada"
        return redirect('/dashboard')->with('success', '¡Tarea eliminada (enviada a la papelera)!');
    }
    
    public function updateStatusAjax(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $task = Task::findOrFail($id);
        
        if ($task->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $task->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    // ==========================================
    // Descargar/Ver Evidencia
    // ==========================================
    public function downloadAttachment(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if ($task->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes ver este archivo.');
        }

        $archivos = $task->attachments ?? [];
        
        // Recibimos la ruta del archivo específico por query string (?path=...)
        $rutaBuscada = $request->query('path');
        $archivoEncontrado = collect($archivos)->firstWhere('path', $rutaBuscada);

        if (!$archivoEncontrado) {
            return redirect('/dashboard')->withErrors(['attachments' => 'El archivo solicitado no forma parte de esta tarea.']);
        }

        $rutaFisica = storage_path('app/private/' . $archivoEncontrado['path']);
        if(!file_exists($rutaFisica)){
            $rutaFisica = storage_path('app/' . $archivoEncontrado['path']); 
        }

        if (file_exists($rutaFisica)) {
            // Usamos el nombre original limpio para la descarga
            $nombreLimpio = \Illuminate\Support\Str::slug(pathinfo($archivoEncontrado['original_name'], PATHINFO_FILENAME));
            $extension = pathinfo($archivoEncontrado['original_name'], PATHINFO_EXTENSION);
            $nombreDescarga = "{$nombreLimpio}.{$extension}";

            return response()->download($rutaFisica, $nombreDescarga);
        }

        return redirect('/dashboard')->withErrors(['attachments' => 'El archivo físico no se encontró en el servidor.']);
    }
}