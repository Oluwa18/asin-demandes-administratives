<?php

namespace Tests\Unit;

use App\Enums\Statut;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatutTransitionTest extends TestCase
{
    public static function transitions(): array
    {
        $autorisees = [
            'DEPOSEE>EN_COURS', 'EN_COURS>VALIDEE', 'EN_COURS>REJETEE',
        ];

        $cas = [];
        foreach (Statut::cases() as $depuis) {
            foreach (Statut::cases() as $vers) {
                $cle = "{$depuis->value}>{$vers->value}";
                $cas[$cle] = [$depuis, $vers, in_array($cle, $autorisees, true)];
            }
        }

        return $cas;
    }

    #[DataProvider('transitions')]
    public function test_matrice_des_transitions(Statut $depuis, Statut $vers, bool $attendu): void
    {
        $this->assertSame($attendu, $depuis->peutPasserA($vers));
    }

    public function test_seuls_valide_et_rejete_sont_finaux(): void
    {
        $this->assertFalse(Statut::DEPOSEE->estFinal());
        $this->assertFalse(Statut::EN_COURS->estFinal());
        $this->assertTrue(Statut::VALIDEE->estFinal());
        $this->assertTrue(Statut::REJETEE->estFinal());
    }
}
