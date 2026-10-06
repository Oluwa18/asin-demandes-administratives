<?php

namespace App\Http\Requests;

use App\Enums\Statut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDemandesRequest extends FormRequest
{
    public const LIMITE_MAX = 20;

    /** Le NPI provient de l'URL : on l'injecte pour le valider comme les autres champs. */
    protected function prepareForValidation(): void
    {
        $this->merge(['npi' => $this->route('npi')]);
    }

    public function rules(): array
    {
        return [
            'npi' => ['required', 'string', 'regex:/^\d{10}$/'],
            'statut' => ['nullable', Rule::enum(Statut::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::LIMITE_MAX],
        ];
    }

    public function messages(): array
    {
        return [
            'npi.regex' => 'Le NPI doit contenir exactement 10 chiffres.',
            'statut.enum' => "Le statut doit être l'une des valeurs : ".implode(', ', Statut::valeurs()).'.',
            'page.integer' => 'La page doit être un entier.',
            'page.min' => 'La page doit être supérieure ou égale à 1.',
            'limit.integer' => 'La limite doit être un entier.',
            'limit.min' => 'La limite doit être supérieure ou égale à 1.',
            'limit.max' => 'La limite ne peut pas dépasser '.self::LIMITE_MAX.' éléments par page.',
        ];
    }

    public function statut(): ?Statut
    {
        return Statut::tryFrom((string) $this->validated('statut'));
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function limite(): int
    {
        return (int) ($this->validated('limit') ?? self::LIMITE_MAX);
    }
}
