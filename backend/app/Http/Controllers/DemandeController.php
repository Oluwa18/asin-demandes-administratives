<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListDemandesRequest;
use App\Http\Requests\StoreDemandeRequest;
use App\Http\Requests\UpdateStatutRequest;
use App\Models\Demande;
use App\Services\DemandeWorkflowService;
use Illuminate\Http\JsonResponse;

class DemandeController extends Controller
{
    public function __construct(private readonly DemandeWorkflowService $workflow) {}

    /** POST /api/demandes */
    public function store(StoreDemandeRequest $request): JsonResponse
    {
        $demande = $this->workflow->deposer($request->validated());

        return response()->json([
            'message' => 'Demande créée avec succès',
            'data' => $demande,
        ], 201);
    }

    /** GET /api/demandes/{demande} */
    public function show(Demande $demande): JsonResponse
    {
        return response()->json(['data' => $demande]);
    }

    /** GET /api/usagers/{npi}/demandes */
    public function indexParUsager(ListDemandesRequest $request, string $npi): JsonResponse
    {
        $resultats = $this->workflow->listerPourUsager($npi, $request->statut(), $request->page(), $request->limite());

        return response()->json([
            'data' => $resultats->items(),
            'meta' => [
                'page' => $resultats->currentPage(),
                'limit' => $resultats->perPage(),
                'total' => $resultats->total(),
                'total_pages' => (int) ceil($resultats->total() / $resultats->perPage()),
            ],
        ]);
    }

    /** PATCH /api/demandes/{demande}/statut */
    public function updateStatut(UpdateStatutRequest $request, Demande $demande): JsonResponse
    {
        $demande = $this->workflow->changerStatut(
            $demande,
            $request->statutCible(),
            $request->validated('motif_rejet'),
        );

        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'data' => $demande,
        ]);
    }
}
