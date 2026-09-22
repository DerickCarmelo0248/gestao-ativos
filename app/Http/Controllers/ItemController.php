<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Item::class);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('items.create', compact('categories'));
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        try {
            Item::create($request->validated());
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'code' => 'Já existe um item com esse código.',
            ]);
        }

        return redirect()
            ->route('items.create')
            ->with('status', 'Item cadastrado com sucesso.');
    }
}