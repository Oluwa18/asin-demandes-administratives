<?php

namespace App\Http\Requests;

use App\Enums\TypeActe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandeRequest extends FormRequest
{
    public const COPIES_MIN = 1;
    public const COPIES_MAX = 5;

    public function rules(): array
    {
        return [
            'npi' => ['required', 'string', 'regex:/^\d{10}$/'],
            'type_acte' => ['required', Rule::enum(TypeActe::class)],
            'nombre_copies' => ['required', 'integer', 'min:'.self::COPIES_MIN, 'max:'.self::COPIES_MAX],
        ];
    }

    public function messages(): array
    {
        return [
            'npi.required' => 'Le NPI est obligatoire.',
            'npi.string' => 'Le NPI doit contenir exactement 10 chiffres.',
            'npi.regex' => 'Le NPI doit contenir exactement 10 chiffres.',
            'type_acte.required' => "Le type d'acte est obligatoire.",
            'type_acte.enum' => "Le type d'acte doit être l'une des valeurs : ".implode(', ', TypeActe::valeurs()).'.',
            'nombre_copies.required' => 'Le nombre de copies est obligatoire.',
            'nombre_copies.integer' => 'Le nombre de copies doit être un entier.',
            'nombre_copies.min' => 'Le nombre de copies doit être compris entre 1 et 5.',
            'nombre_copies.max' => 'Le nombre de copies doit être compris entre 1 et 5.',
        ];
    }
}
