<?php

namespace App\Http\Requests;

use App\Models\StockBalance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'recordExit',
            StockBalance::class
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('ticket_number'))) {
            $this->merge([
                'ticket_number' => trim($this->input('ticket_number')),
            ]);
        }
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
            'technician_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('technicians', 'id')
                    ->where('is_active', true),
            ],
            'ticket_number' => [
                'required',
                'string',
                'max:100',
            ],
            'destination_establishment_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('establishments', 'id')
                    ->where('is_active', true),
            ],
            'destination_sector_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('sectors', 'id')
                    ->where('is_active', true),
            ],
            'replacement_required' => [
                'required',
                'boolean',
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
            'string' => 'O campo :attribute deve ser um texto.',
            'item_id.exists' => 'Selecione um item ativo com controle por quantidade.',
            'unit_id.exists' => 'Selecione uma unidade de estoque ativa.',
            'technician_id.exists' => 'Selecione um técnico ativo.',
            'destination_establishment_id.exists' => 'Selecione um estabelecimento ativo.',
            'destination_sector_id.exists' => 'Selecione um setor ativo.',
            'quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'quantity.max' => 'Cada saída pode registrar até 10.000 unidades.',
            'ticket_number.max' => 'O chamado deve ter até 100 caracteres.',
            'replacement_required.boolean' => 'Informe se a saída exige reposição.',
            'notes.max' => 'A observação deve ter até 2.000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id' => 'item',
            'unit_id' => 'unidade de origem',
            'quantity' => 'quantidade',
            'technician_id' => 'técnico',
            'ticket_number' => 'número do chamado',
            'destination_establishment_id' => 'estabelecimento de destino',
            'destination_sector_id' => 'setor de destino',
            'replacement_required' => 'reposição necessária',
            'notes' => 'observação',
        ];
    }
}