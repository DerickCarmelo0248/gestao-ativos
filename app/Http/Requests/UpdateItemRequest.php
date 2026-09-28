<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateItemRequest extends StoreItemRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = ['required', 'string', 'max:50', 'regex:/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/',
            Rule::unique('items', 'code')->ignore($this->route('item'))];
        $rules['tracking_type'] = ['required', Rule::in([$this->route('item')->tracking_type])];
        $rules['minimum_stock'] = ['required', 'integer', 'min:0', 'max:2147483647'];
        return $rules;
    }

    public function messages(): array
    {
        return array_replace(parent::messages(), [
            'tracking_type.in' => 'O tipo de controle não pode ser alterado após o cadastro.',
        ]);
    }
}
