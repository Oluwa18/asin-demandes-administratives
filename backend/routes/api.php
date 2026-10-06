<?php

use App\Http\Controllers\DemandeController;
use App\Http\Controllers\StatistiqueController;
use Illuminate\Support\Facades\Route;

Route::post('/demandes', [DemandeController::class, 'store']);
Route::get('/demandes/{demande}', [DemandeController::class, 'show'])->whereUuid('demande');
Route::patch('/demandes/{demande}/statut', [DemandeController::class, 'updateStatut'])->whereUuid('demande');

Route::get('/usagers/{npi}/demandes', [DemandeController::class, 'indexParUsager']);

Route::get('/statistiques/statuts', [StatistiqueController::class, 'statuts']);
