<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('asset')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['patrimony', 'serial_number', 'notes'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'patrimony' => ['required', 'string', 'max:50', Rule::unique('assets', 'patrimony')->ignore($this->route('asset'))],
            'serial_number' => ['present', 'nullable', 'string', 'max:100'],
            'notes' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patrimony.unique' => 'Já existe um equipamento com esse patrimônio.',
            'patrimony.required' => 'Informe o patrimônio.',
            'max' => 'O campo :attribute deve ter até :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return ['patrimony' => 'patrimônio', 'serial_number' => 'número de série', 'notes' => 'observações'];
    }
}
