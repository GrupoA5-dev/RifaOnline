<?php

namespace App\Http\Requests;

use App\Enums\AllocationMode;
use App\Models\Raffle;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $raffle = $this->raffle();
        $rules = [
            'name' => $raffle?->collect_name
                ? ['required', 'string', 'min:3', 'max:120']
                : ['nullable', 'string', 'max:120'],
            'email' => $raffle?->collect_email
                ? ['required', 'email:rfc', 'max:190']
                : ['nullable', 'email:rfc', 'max:190'],
            // WhatsApp é obrigatório em todas as campanhas porque funciona como
            // chave de acesso do participante à área "Meus números".
            'phone' => ['required', 'string', 'max:20'],
            'document' => $raffle?->collect_document
                ? ['required', 'string', new Cpf]
                : ['nullable', 'string'],
            'terms' => ['accepted'],
        ];

        if ($raffle?->allocation_mode === AllocationMode::Manual) {
            $max = $raffle->max_purchase ?? 100000;
            $rules['quantity'] = ['nullable', 'integer', 'min:1', 'max:100000'];
            $rules['numbers'] = ['required', 'array', 'min:'.max(1, (int) $raffle->min_purchase), 'max:'.max(1, (int) $max)];
            $rules['numbers.*'] = [
                'required',
                'integer',
                'distinct',
                'min:0',
                'max:'.max(0, (int) $raffle->total_numbers - 1),
            ];
        } else {
            $rules['quantity'] = ['required', 'integer', 'min:1', 'max:100000'];
            $rules['numbers'] = ['nullable', 'array'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'phone.required' => 'Informe seu WhatsApp ou telefone.',
            'document.required' => 'Informe seu CPF.',
            'quantity.required' => 'Escolha a quantidade de números.',
            'quantity.integer' => 'A quantidade informada é inválida.',
            'numbers.required' => 'Escolha os números desejados.',
            'numbers.min' => 'Selecione a quantidade mínima exigida pela campanha.',
            'numbers.max' => 'Você selecionou mais números do que o permitido.',
            'numbers.*.distinct' => 'Existem números repetidos na seleção.',
            'terms.accepted' => 'Você precisa aceitar o regulamento para continuar.',
        ];
    }

    private function raffle(): ?Raffle
    {
        $slug = (string) $this->route('slug');

        if ($slug === '') {
            return null;
        }

        return Raffle::query()->where('slug', $slug)->first();
    }
}
