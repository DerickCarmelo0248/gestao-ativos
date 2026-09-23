<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordExit', Asset::class) ?? false;
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
            'asset_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('assets', 'id')
                    ->where('status', 'available'),
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
            'integer' => 'Selecione um valor válido para :attribute.',
            'string' => 'O campo :attribute deve ser um texto.',
            'asset_id.exists' => 'Selecione um equipamento disponível no estoque.',
            'technician_id.exists' => 'Selecione um técnico ativo.',
            'ticket_number.max' => 'O chamado deve ter até 100 caracteres.',
            'destination_establishment_id.exists' => 'Selecione um estabelecimento ativo.',
            'destination_sector_id.exists' => 'Selecione um setor ativo.',
            'replacement_required.boolean' => 'Informe se a saída exige reposição.',
            'notes.max' => 'A observação deve ter até 2.000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'asset_id' => 'equipamento',
            'technician_id' => 'técnico',
            'ticket_number' => 'número do chamado',
            'destination_establishment_id' => 'estabelecimento de destino',
            'destination_sector_id' => 'setor de destino',
            'replacement_required' => 'reposição necessária',
            'notes' => 'observação',
        ];
    }
}