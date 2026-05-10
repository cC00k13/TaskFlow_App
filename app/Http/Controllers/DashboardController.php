<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Iniciamos la consulta base (solo tareas del estudiante actual)
        $query = Task::with('labels')->where('user_id', Auth::id());

        // 2. Escuchamos el filtro de ordenamiento que viene del frontend
        $orden = $request->input('ordenar_por', 'fecha_proxima');

        switch ($orden) {
            case 'mas_recientes':
                $query->orderBy('created_at', 'desc');
                break;
            case 'prioridad_alta':
                $query->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END");
                break;
            case 'fecha_proxima':
            default:
                // Si la fecha es nula, los mandamos al final
                $query->orderByRaw('ISNULL(due_date), due_date ASC');
                break;
        }

        // 3. Ejecutamos la consulta para traer las tareas ya ordenadas
        $tasks = $query->get();

        // 4. Traemos el catálogo de etiquetas del usuario
        $labels = Label::where('user_id', Auth::id())->get();

        // 5. Mandamos todo a la vista del dashboard
        return view('dashboard', compact('tasks', 'labels'));
    }
}