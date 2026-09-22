<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('patrimony'))) {
            $normalized['patrimony'] = trim(
                $this->input('patrimony')
            );
        }

        if (is_string($this->input('serial_number'))) {
            $serial = trim($this->input('serial_number'));

            $normalized['serial_number'] = $serial === ''
                ? null
                : $serial;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('items', 'id')
                    ->where('tracking_type', 'individual')
                    ->where('is_active', true),
            ],
            'unit_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('units', 'id')
                    ->where('is_active', true),
            ],
            'patrimony' => [
                'bail',
                'required',
                'string',
                'max:50',
                'unique:assets,patrimony',
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute deve ter até :max caracteres.',
            'item_id.integer' => 'Selecione um item válido.',
            'item_id.exists' => 'Selecione um item ativo com controle individual.',
            'unit_id.integer' => 'Selecione uma unidade válida.',
            'unit_id.exists' => 'Selecione uma unidade ativa.',
            'patrimony.unique' => 'Esse patrimônio já está cadastrado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id' => 'item',
            'unit_id' => 'unidade',
            'patrimony' => 'patrimônio',
            'serial_number' => 'número de série',
        ];
    }
}