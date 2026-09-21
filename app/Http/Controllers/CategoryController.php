<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(
        StoreCategoryRequest $request
    ): RedirectResponse {
        try {
            Category::create($request->validated());
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'name' => 'Já existe uma categoria com esse nome.',
            ]);
        }

        return redirect()
            ->route('categories.create')
            ->with('status', 'Categoria cadastrada com sucesso.');
    }
}