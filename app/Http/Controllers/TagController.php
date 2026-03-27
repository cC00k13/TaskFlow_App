<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Models\User;

class TagController extends Controller
{
    /**
     * Muestra una lista de las etiquetas pertenencientes al usuario
     */
    public function index(): View
    {
        $tags = request()->user()->tags()
        ->withCount('tasks')
        ->orderBy('name')
        ->paginate(12);

        return view('tags.index', compact('tags'));
    }

    /**
     * Muestra el formulario para crear una nueva etiqueta
     */
    public function create(): View
    {
        return view('tags.create');
    }

    /**
     * Almacena una nueva etiqueta
     */
    public function store(StoreTagRequest $request): RedirectResponse
    {
        $tag = request()->user()->tags()->create($request->validated());

        return redirect()
        ->route('tags.index')
        ->with('success', "Etiqueta '{$tag->name}' creada correctamente.");
    }
    /**
     * Muestra el formulario para actualizar la etiqueta
     */
    public function edit(Tag $tag): View
    {
        Gate::authorize('update', $tag);

        return view('tags.edit', compact('tag'));
    }

    /**
     * Actualiza la etiqueta
     */
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('update', $tag);

        $oldName = $tag->name;
        $tag->update($request->validated());

        return redirect()
            ->route('tags.index')
            ->with('success', "Etiqueta '{$oldName}' actualizada a '{$tag->name}' correctamente.");
    }

    /**
     * Elimina la etiqueta especificada
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        $name = $tag->name;
        
        // Detach from all tasks before deleting
        $tag->tasks()->detach();
        $tag->delete();

        return redirect()
            ->route('tags.index')
            ->with('success', "Etiqueta '{$name}' eliminada correctamente");
    }
}