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

    public function index(Request $request)
    {
        $query = Task::where('user_id', Auth::id());

        // Filter by priority if provided
        if ($request->has('priority') && $request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Determine sort column (deadline or created_at as default)
        $sortBy = $request->input('sort_by', 'created_at');

        // Only allow specific columns to prevent SQL injection
        $allowedSortColumns = ['deadline', 'created_at', 'priority', 'title'];

        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        // Determine sort direction
        $sortDirection = $request->input('sort', 'desc'); // default to latest

        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        $tasks = $query->get();

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
