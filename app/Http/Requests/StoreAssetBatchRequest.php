<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['patrimony_start', 'patrimony_end'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
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
            'patrimony_start' => [
                'bail',
                'required',
                'string',
                'regex:/\A[0-9]{1,9}\z/',
            ],
            'patrimony_end' => [
                'bail',
                'required',
                'string',
                'regex:/\A[0-9]{1,9}\z/',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $start = $this->input('patrimony_start');
                $end = $this->input('patrimony_end');

                if (
                    (str_starts_with($start, '0') ||
                     str_starts_with($end, '0')) &&
                    strlen($start) !== strlen($end)
                ) {
                    $validator->errors()->add(
                        'patrimony_end',
                        'Quando houver zeros à esquerda, use o mesmo número de dígitos nos dois campos.'
                    );

                    return;
                }

                $quantity = (int) $end - (int) $start + 1;

                if ($quantity < 1 || $quantity > 500) {
                    $validator->errors()->add(
                        'patrimony_end',
                        'O final deve ser maior ou igual ao início, com até 500 equipamentos por lote.'
                    );

                    return;
                }

                $patrimonies = [];

                for ($number = (int) $start; $number <= (int) $end; $number++) {
                    $patrimonies[] = str_pad(
                        (string) $number,
                        strlen($start),
                        '0',
                        STR_PAD_LEFT
                    );
                }

                $conflicts = Asset::query()
                    ->whereIn('patrimony', $patrimonies)
                    ->orderBy('patrimony')
                    ->pluck('patrimony');

                if ($conflicts->isNotEmpty()) {
                    $validator->errors()->add(
                        'patrimony_start',
                        'Patrimônios já cadastrados: '.$conflicts->implode(', ')
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'integer' => 'Selecione um valor válido para :attribute.',
            'item_id.exists' => 'Selecione um item ativo com controle individual.',
            'unit_id.exists' => 'Selecione uma unidade ativa.',
            'patrimony_start.regex' => 'O patrimônio inicial deve conter de 1 a 9 dígitos.',
            'patrimony_end.regex' => 'O patrimônio final deve conter de 1 a 9 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id' => 'item',
            'unit_id' => 'unidade',
            'patrimony_start' => 'patrimônio inicial',
            'patrimony_end' => 'patrimônio final',
        ];
    }
}