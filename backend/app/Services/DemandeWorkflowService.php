<?php

namespace App\Services;

use App\Enums\Statut;
use App\Exceptions\MotifRejetManquantException;
use App\Exceptions\TransitionInterditeException;
use App\Models\Demande;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DemandeWorkflowService
{
    /**
     * @param  array{npi: string, type_acte: string, nombre_copies: int}  $donnees
     */
    public function deposer(array $donnees): Demande
    {
        // Le statut initial est imposé par le modèle (DEPOSEE), jamais par le client.
        return Demande::create($donnees)->refresh();
    }

    public function listerPourUsager(string $npi, ?Statut $statut, int $page, int $limite): LengthAwarePaginator
    {
        return Demande::query()
            ->where('npi', $npi)
            ->when($statut, fn ($query) => $query->where('statut', $statut->value))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(perPage: $limite, page: $page);
    }

    /**
     * Applique une transition du cycle de vie.
     *
     * @throws MotifRejetManquantException
     * @throws TransitionInterditeException
     */
    public function changerStatut(Demande $demande, Statut $cible, ?string $motifRejet = null): Demande
    {
        $motif = $motifRejet !== null ? trim($motifRejet) : '';

        if ($cible === Statut::REJETEE && $motif === '') {
            throw new MotifRejetManquantException;
        }

        if (! $demande->statut->peutPasserA($cible)) {
            throw TransitionInterditeException::entre($demande->statut, $cible);
        }

        // La condition sur le statut courant protège contre deux mises à jour concurrentes.
        $misesAJour = Demande::query()
            ->whereKey($demande->id)
            ->where('statut', $demande->statut->value)
            ->update([
                'statut' => $cible->value,
                'motif_rejet' => $cible === Statut::REJETEE ? $motif : null,
                'updated_at' => now(),
            ]);

        if ($misesAJour === 0) {
            throw new TransitionInterditeException('La demande a été modifiée entre-temps, veuillez recharger et réessayer.');
        }

        return $demande->refresh();
    }

    /** @return array<string, int> les 4 statuts, toujours présents */
    public function statistiquesParStatut(): array
    {
        $comptes = Demande::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $stats = [];
        foreach (Statut::cases() as $statut) {
            $stats[$statut->value] = (int) ($comptes[$statut->value] ?? 0);
        }

        return $stats;
    }
}
