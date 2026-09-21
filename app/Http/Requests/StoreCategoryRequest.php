<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Closure;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
{
    return $this->user()?->can('create', Category::class) ?? false;
}

    protected function prepareForValidation(): void
    {
        if (is_string($this->name)) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }

    public function rules(): array
{
    return [
        'name' => [
            'bail',
            'required',
            'string',
            'max:100',
            function (string $attribute, mixed $value, Closure $fail): void {
                $exists = DB::table('categories')
                    ->whereRaw(
                        'LOWER(TRIM(name)) = LOWER(TRIM(CAST(? AS TEXT)))',
                        [$value]
                    )
                    ->exists();

                if ($exists) {
                    $fail('Já existe uma categoria com esse nome.');
                }
            },
        ],
        'description' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ];
}

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da categoria.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter até 100 caracteres.',
            'description.string' => 'A descrição deve ser um texto.',
            'description.max' => 'A descrição deve ter até 2.000 caracteres.',
        ];
    }
}