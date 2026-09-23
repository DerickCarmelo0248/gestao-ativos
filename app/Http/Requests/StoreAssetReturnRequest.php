<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordReturn', Asset::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_id' => [
                'required',
                'integer',
                Rule::exists('assets', 'id')->where('status', 'in_use'),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where('is_active', true),
            ],
            'technician_id' => [
                'required',
                'integer',
                Rule::exists('technicians', 'id')->where('is_active', true),
            ],
            'return_status' => [
                'required',
                Rule::in(['available', 'awaiting_disposal']),
            ],
            'notes' => [
                'nullable',
                'required_if:return_status,awaiting_disposal',
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
            'asset_id.exists' => 'Selecione um equipamento em uso.',
            'unit_id.exists' => 'Selecione uma unidade de estoque ativa.',
            'technician_id.exists' => 'Selecione um técnico ativo.',
            'return_status.in' => 'Selecione uma condição válida.',
            'notes.required_if' => 'Descreva o motivo do encaminhamento para descarte.',
            'notes.string' => 'A observação deve ser um texto.',
            'notes.max' => 'A observação deve ter até 2.000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'asset_id' => 'equipamento',
            'unit_id' => 'estoque de recebimento',
            'technician_id' => 'técnico que devolveu',
            'return_status' => 'condição do equipamento',
            'notes' => 'observação',
        ];
    }
}