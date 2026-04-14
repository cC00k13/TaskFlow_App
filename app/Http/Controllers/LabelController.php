<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LabelController extends Controller
{
    // Index: Devuelve las etiquetas del usuario en formato JSON (útil si después usas Fetch/Axios)
    public function index()
    {
        return response()->json(Label::where('user_id', Auth::id())->get());
    }

    // Store: Guarda una nueva categoría
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:30',
                // Previene duplicados del mismo usuario, ignorando las eliminadas lógicamente
                Rule::unique('labels', 'name')->where('user_id', Auth::id())->whereNull('deleted_at')
            ],
            // Validación de formato hexadecimal
            'color'  => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/']
        ], [
            'nombre.max' => 'El título no puede exceder los 30 caracteres.',
            'nombre.unique' => 'Ese nombre de etiqueta ya está en uso.',
            'color.regex' => 'El color debe ser un formato hexadecimal válido.'
        ]);

        Label::create([
            'user_id' => Auth::id(),
            'name'    => $request->nombre,
            'color'   => $request->color,
        ]);

        // CAMBIADO: Redirección explícita para evitar pantallas en blanco
        return redirect('/dashboard')->with('success', '¡Etiqueta creada exitosamente!');
    }

    // Update: Modifica nombre o color
    public function update(Request $request, $id)
    {
        $label = Label::findOrFail($id);

        // Autorización: Seguridad para que nadie modifique IDs por URL
        if ($label->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes editar esta etiqueta.');
        }

        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:30',
                Rule::unique('labels', 'name')
                    ->where('user_id', Auth::id())
                    ->whereNull('deleted_at')
                    ->ignore($label->id)
            ],
            'color'  => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/']
        ], [
            'nombre.unique' => 'Ese nombre de etiqueta ya está en uso.',
        ]);

        $label->update([
            'name'  => $request->nombre,
            'color' => $request->color,
        ]);

        // CAMBIADO: Redirección explícita
        return redirect('/dashboard')->with('success', '¡Etiqueta actualizada!');
    }

    // Destroy: Eliminación lógica (SoftDelete)
    public function destroy($id)
    {
        $label = Label::findOrFail($id);

        // Autorización
        if ($label->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado. No puedes eliminar esta etiqueta.');
        }

        // NUEVO: Desvincular la etiqueta de todas las tareas antes de eliminarla
        // Esto evita conflictos de llaves foráneas y hace que el borrado sea instantáneo.
        $label->tasks()->detach(); 

        // Al usar SoftDeletes en el modelo, esto NO la borra físicamente, solo llena 'deleted_at'
        $label->delete();

        // CAMBIADO: Redirección explícita
        return redirect('/dashboard')->with('success', '¡Etiqueta eliminada correctamente!');
    }
}