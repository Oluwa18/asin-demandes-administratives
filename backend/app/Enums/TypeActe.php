<?php

namespace App\Enums;

enum TypeActe: string
{
    case ACTE_NAISSANCE = 'ACTE_NAISSANCE';
    case CASIER_JUDICIAIRE = 'CASIER_JUDICIAIRE';
    case CERTIFICAT_RESIDENCE = 'CERTIFICAT_RESIDENCE';

    /** @return list<string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
