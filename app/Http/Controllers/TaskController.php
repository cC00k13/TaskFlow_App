<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function create(): View 
    {
        return view('task.create');
    }

    public function store(Request $request): RedirectResponse 
    {
        $validated = $request -> validate([
            'title' => 'required|max:255',
            'date' => 'required', Rule::date()->todayOrAfter(),
        ],);

        return redirect('/dashboard');
    }
}
