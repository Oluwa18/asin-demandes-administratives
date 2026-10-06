<?php

namespace App\Exceptions;

use App\Enums\Statut;
use RuntimeException;

class TransitionInterditeException extends RuntimeException
{
    public static function entre(Statut $depuis, Statut $vers): self
    {
        return new self("Transition interdite de {$depuis->value} vers {$vers->value}.");
    }
}
