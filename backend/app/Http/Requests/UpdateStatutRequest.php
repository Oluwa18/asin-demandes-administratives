<?php

namespace App\Http\Requests;

use App\Enums\Statut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::enum(Statut::class)],
            'motif_rejet' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn () => $this->input('statut') === Statut::REJETEE->value),
                Rule::prohibitedIf(fn () => $this->input('statut') !== Statut::REJETEE->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut est obligatoire.',
            'statut.enum' => "Le statut doit être l'une des valeurs : ".implode(', ', Statut::valeurs()).'.',
            'motif_rejet.required' => 'Le motif de rejet est obligatoire.',
            'motif_rejet.prohibited' => 'Le motif de rejet ne peut être renseigné que pour le statut REJETEE.',
            'motif_rejet.string' => 'Le motif de rejet doit être une chaîne de caractères.',
        ];
    }

    public function statutCible(): Statut
    {
        return Statut::from($this->validated('statut'));
    }
}
