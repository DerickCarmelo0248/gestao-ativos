<?php

namespace App\Http\Requests;

use App\Models\StockBalance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'recordEntry',
            StockBalance::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('items', 'id')
                    ->where('tracking_type', 'quantity')
                    ->where('is_active', true),
            ],
            'unit_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('units', 'id')
                    ->where('is_active', true),
            ],
            'quantity' => [
                'bail',
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'item_id.exists' => 'Selecione um item ativo com controle por quantidade.',
            'unit_id.exists' => 'Selecione uma unidade ativa.',
            'quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'quantity.max' => 'Cada entrada pode registrar até 10.000 unidades.',
            'notes.string' => 'A observação deve ser um texto.',
            'notes.max' => 'A observação deve ter até 2.000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id' => 'item',
            'unit_id' => 'unidade',
            'quantity' => 'quantidade',
            'notes' => 'observação',
        ];
    }
}