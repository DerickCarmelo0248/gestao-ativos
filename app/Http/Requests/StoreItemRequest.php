<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Item::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('code'))) {
            $normalized['code'] = strtoupper(
                trim($this->input('code'))
            );
        }

        if (is_string($this->input('name'))) {
            $normalized['name'] = trim($this->input('name'));
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'minimum_stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
            'category_id' => [
                'bail',
                'required',
                'integer',
                'exists:categories,id',
            ],
            'code' => [
                'bail',
                'required',
                'string',
                'max:50',
                'regex:/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/',
                'unique:items,code',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'tracking_type' => [
                'required',
                Rule::in(['quantity', 'individual']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'minimum_stock.integer' => 'O estoque mínimo deve ser um número inteiro.',
            'minimum_stock.min' => 'O estoque mínimo não pode ser negativo.',
            'minimum_stock.max' => 'O estoque mínimo deve ser no máximo 2147483647.',
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute deve ter até :max caracteres.',
            'category_id.integer' => 'Selecione uma categoria válida.',
            'category_id.exists' => 'A categoria selecionada não existe.',
            'code.regex' => 'Use letras sem acentos e números, separados opcionalmente por hífen.',
            'code.unique' => 'Já existe um item com esse código.',
            'tracking_type.in' => 'Selecione um tipo de controle válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'categoria',
            'code' => 'código',
            'name' => 'nome',
            'description' => 'descrição',
            'tracking_type' => 'tipo de controle',
            'minimum_stock' => 'estoque mínimo',
        ];
    }
}
