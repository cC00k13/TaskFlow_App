<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Label;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Traemos las tareas (con sus etiquetas) del usuario que inició sesión
        $tasks = Task::with('labels')
                     ->where('user_id', Auth::id())
                     ->orderBy('due_date', 'asc')
                     ->get();

        // 2. Traemos las etiquetas que ha creado este usuario
        $labels = Label::where('user_id', Auth::id())->get();

        // 3. Mandamos las variables correctas (en inglés) a tu vista
        return view('dashboard', compact('tasks', 'labels'));
    }
}