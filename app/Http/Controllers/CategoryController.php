<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Item;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $category->update($request->validated());
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'name' => 'Já existe uma categoria com esse nome.',
            ]);
        }

        return redirect()->route('categories.index')
            ->with('status', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        try {
            DB::transaction(function () use ($category) {
                $locked = Category::query()->lockForUpdate()->findOrFail($category->id);
                if (Item::where('category_id', $locked->id)->exists()) {
                    throw ValidationException::withMessages([
                        'category' => 'Não é possível excluir uma categoria vinculada a itens. Altere a categoria desses itens primeiro.',
                    ]);
                }
                $locked->delete();
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23503') {
                throw $exception;
            }
            throw ValidationException::withMessages([
                'category' => 'Não é possível excluir uma categoria vinculada a outros registros.',
            ]);
        }

        return redirect()->route('categories.index')
            ->with('status', 'Categoria excluída com sucesso.');
    }

public function index(): View
{
    Gate::authorize('viewAny', Category::class);

    $categories = Category::query()
        ->orderBy('name')
        ->orderBy('id')
        ->paginate(15);

    return view('categories.index', compact('categories'));
}

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
