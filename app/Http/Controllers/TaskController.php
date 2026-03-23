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
    public function create(): View 
    {
        return view('task.create');
    }

    public function index()
    {
        
        $tasks = Auth::user()
            ->tasks()
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
}
