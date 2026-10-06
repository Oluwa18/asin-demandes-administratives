<?php

namespace App\Models;

use App\Enums\Statut;
use App\Enums\TypeActe;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $npi
 * @property TypeActe $type_acte
 * @property int $nombre_copies
 * @property Statut $statut
 * @property string|null $motif_rejet
 */
class Demande extends Model
{
    use HasUuids;

    protected $table = 'demandes';

    /** Le statut n'est volontairement pas assignable en masse : il est piloté par le workflow. */
    protected $fillable = ['npi', 'type_acte', 'nombre_copies'];

    protected $attributes = [
        'statut' => 'DEPOSEE',
        'motif_rejet' => null,
    ];

    protected function casts(): array
    {
        return [
            'type_acte' => TypeActe::class,
            'statut' => Statut::class,
            'nombre_copies' => 'integer',
        ];
    }
}
