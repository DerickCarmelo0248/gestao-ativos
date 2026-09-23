<?php

namespace App\Http\Requests;

use App\Models\AssetReplacementRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteAssetReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $replacement = $this->route('replacement');

        return $replacement instanceof AssetReplacementRequest
            && ($this->user()?->can('complete', $replacement) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (['patrimony', 'serial_number', 'notes'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);

                $values[$field] = $value === '' ? null : $value;
            }
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        return [
            'patrimony' => [
                'bail',
                'required',
                'string',
                'max:50',
                Rule::unique('assets', 'patrimony'),
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'received' => [
                'required',
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patrimony.required' =>
                'Informe o patrimônio do equipamento recebido.',
            'patrimony.string' =>
                'O patrimônio deve ser um texto.',
            'patrimony.max' =>
                'O patrimônio deve ter até 50 caracteres.',
            'patrimony.unique' =>
                'Este patrimônio já está cadastrado. Informe o patrimônio do novo equipamento.',
            'serial_number.string' =>
                'O número de série deve ser um texto.',
            'serial_number.max' =>
                'O número de série deve ter até 100 caracteres.',
            'notes.string' =>
                'A observação deve ser um texto.',
            'notes.max' =>
                'A observação deve ter até 2.000 caracteres.',
            'received.required' =>
                'Confirme o recebimento do equipamento.',
            'received.accepted' =>
                'Confirme o recebimento do equipamento.',
        ];
    }
}