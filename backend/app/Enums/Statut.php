<?php

namespace App\Enums;

enum Statut: string
{
    case DEPOSEE = 'DEPOSEE';
    case EN_COURS = 'EN_COURS';
    case VALIDEE = 'VALIDEE';
    case REJETEE = 'REJETEE';

    /**
     * Cycle de vie : source unique de vérité des transitions autorisées.
     *
     * @return list<self>
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::DEPOSEE => [self::EN_COURS],
            self::EN_COURS => [self::VALIDEE, self::REJETEE],
            self::VALIDEE, self::REJETEE => [],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    public function estFinal(): bool
    {
        return $this->transitionsAutorisees() === [];
    }

    /** @return list<string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
