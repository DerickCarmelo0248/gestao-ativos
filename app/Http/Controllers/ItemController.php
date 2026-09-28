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
use App\Http\Requests\UpdateItemRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage', Item::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $search = trim($filters['search'] ?? '');
        $items = Item::with('category')->when($search !== '', function ($query) use ($search) {
            $query->where(fn ($q) => $q->where('name', 'ilike', '%'.$search.'%')
                ->orWhere('code', 'ilike', '%'.$search.'%'));
        })->orderBy('name')->orderBy('id')->paginate(20)->withQueryString();
        return view('items.index', compact('items', 'search'));
    }

    public function edit(Item $item): View
    {
        Gate::authorize('update', $item);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        return view('items.edit', compact('item', 'categories'));
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        try {
            $data = $request->validated();
            unset($data['tracking_type']);
            $item->update($data);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['code' => 'Já existe um item com esse código.']);
        }
        return redirect()->route('items.index')->with('status', 'Item atualizado com sucesso.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        Gate::authorize('delete', $item);
        $message = 'Este item possui equipamentos, saldos ou histórico vinculados e não pode ser excluído.';
        try {
            DB::transaction(function () use ($item, $message) {
                $locked = Item::query()->lockForUpdate()->findOrFail($item->id);
                foreach (['assets', 'stock_balances', 'stock_movements', 'disposal_container_items'] as $table) {
                    if (DB::table($table)->where('item_id', $locked->id)->exists()) {
                        throw ValidationException::withMessages(['item' => $message]);
                    }
                }
                $locked->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }
            throw ValidationException::withMessages(['item' => $message]);
        }
        return redirect()->route('items.index')->with('status', 'Item excluído com sucesso.');
    }

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
