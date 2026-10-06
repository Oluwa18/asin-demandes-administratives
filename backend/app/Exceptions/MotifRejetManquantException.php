<?php

namespace App\Exceptions;

use RuntimeException;

class MotifRejetManquantException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Le motif de rejet est obligatoire.');
    }
}
