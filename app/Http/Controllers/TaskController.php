<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function create(): View 
    {
        return view('task.create');
    }

    public function index()
    {

        //Descomentar cuando se haya arreglado el problema de la relacion entre el User model y Task controller
        /*$tasks = Auth::user()
            ->tasks()
            ->latest()  
            ->get();

        return view('tasks.index', compact('tasks')); */

        $tasks = Task::where('user_id', Auth::id())
        ->latest()
        ->get();

        return view('tasks.index', compact('tasks'));
    }

    public function store(Request $request): RedirectResponse 
    {
        $validated = $request -> validate([
            'title' => 'required|max:255',
            'date' => 'required', Rule::date()->todayOrAfter(),
        ],);

        Task::create($validated);

        return redirect('/dashboard');
    }

    public function edit(Task $task): View
    {
                
        return view('tasks.edit', compact('task'));
    }

    public function patch(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'date' => 'sometimes|date|after_or_equal:today',
        ]);
        
        $task->update($validated);
        
        return redirect()
            ->back()
            ->with('success', 'Task updated successfully');
    }
}
