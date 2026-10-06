<?php

namespace App\Http\Controllers;

use App\Services\DemandeWorkflowService;
use Illuminate\Http\JsonResponse;

class StatistiqueController extends Controller
{
    /** GET /api/statistiques/statuts */
    public function statuts(DemandeWorkflowService $workflow): JsonResponse
    {
        return response()->json($workflow->statistiquesParStatut());
    }
}
