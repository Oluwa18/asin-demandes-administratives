<?php

namespace Tests\Feature;

use App\Enums\Statut;
use App\Models\Demande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemandeApiTest extends TestCase
{
    use RefreshDatabase;

    private const NPI = '1234567890';

    private function creer(array $surcharge = []): array
    {
        return $this->postJson('/api/demandes', array_merge([
            'npi' => self::NPI,
            'type_acte' => 'ACTE_NAISSANCE',
            'nombre_copies' => 2,
        ], $surcharge))->assertCreated()->json('data');
    }

    private function demandeAvecStatut(Statut $statut, string $npi = self::NPI): Demande
    {
        $demande = Demande::create(['npi' => $npi, 'type_acte' => 'CASIER_JUDICIAIRE', 'nombre_copies' => 1]);
        $demande->statut = $statut;
        $demande->save();

        return $demande;
    }

    private function changer(string $id, array $payload)
    {
        return $this->patchJson("/api/demandes/{$id}/statut", $payload);
    }

    // ---------- Création ----------

    public function test_creation_valide_avec_statut_initial_deposee(): void
    {
        $this->postJson('/api/demandes', [
            'npi' => self::NPI,
            'type_acte' => 'ACTE_NAISSANCE',
            'nombre_copies' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Demande créée avec succès')
            ->assertJsonPath('data.npi', self::NPI)
            ->assertJsonPath('data.type_acte', 'ACTE_NAISSANCE')
            ->assertJsonPath('data.nombre_copies', 2)
            ->assertJsonPath('data.statut', 'DEPOSEE')
            ->assertJsonPath('data.motif_rejet', null)
            ->assertJsonStructure(['data' => ['id', 'created_at', 'updated_at']]);

        $this->assertDatabaseCount('demandes', 1);
    }

    public function test_le_client_ne_peut_pas_imposer_le_statut_initial(): void
    {
        $data = $this->creer(['statut' => 'VALIDEE']);

        $this->assertSame('DEPOSEE', $data['statut']);
    }

    public function test_chaque_demande_recoit_un_identifiant_unique(): void
    {
        $this->assertNotSame($this->creer()['id'], $this->creer()['id']);
    }

    public static function donneesInvalides(): array
    {
        return [
            'NPI trop court' => [['npi' => '123456789'], 'npi'],
            'NPI trop long' => [['npi' => '12345678901'], 'npi'],
            'NPI avec lettres' => [['npi' => '12345abcde'], 'npi'],
            'NPI absent' => [['npi' => null], 'npi'],
            "type d'acte invalide" => [['type_acte' => 'PASSEPORT'], 'type_acte'],
            'copies = 0' => [['nombre_copies' => 0], 'nombre_copies'],
            'copies = 6' => [['nombre_copies' => 6], 'nombre_copies'],
            'copies non entier' => [['nombre_copies' => 'deux'], 'nombre_copies'],
        ];
    }

    #[DataProvider('donneesInvalides')]
    public function test_creation_refusee_si_donnees_invalides(array $surcharge, string $champ): void
    {
        $this->postJson('/api/demandes', array_merge([
            'npi' => self::NPI,
            'type_acte' => 'ACTE_NAISSANCE',
            'nombre_copies' => 2,
        ], $surcharge))
            ->assertStatus(400)
            ->assertJsonPath('statusCode', 400)
            ->assertJsonValidationErrors($champ);

        $this->assertDatabaseCount('demandes', 0);
    }

    public function test_message_npi_explicite(): void
    {
        $this->postJson('/api/demandes', ['npi' => '123', 'type_acte' => 'ACTE_NAISSANCE', 'nombre_copies' => 1])
            ->assertStatus(400)
            ->assertJsonPath('message.0', 'Le NPI doit contenir exactement 10 chiffres.');
    }

    // ---------- Cycle de vie ----------

    public function test_deposee_vers_en_cours(): void
    {
        $id = $this->creer()['id'];

        $this->changer($id, ['statut' => 'EN_COURS'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'EN_COURS');
    }

    public function test_en_cours_vers_validee(): void
    {
        $demande = $this->demandeAvecStatut(Statut::EN_COURS);

        $this->changer($demande->id, ['statut' => 'VALIDEE'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'VALIDEE')
            ->assertJsonPath('data.motif_rejet', null);
    }

    public function test_en_cours_vers_rejetee_avec_motif(): void
    {
        $demande = $this->demandeAvecStatut(Statut::EN_COURS);

        $this->changer($demande->id, ['statut' => 'REJETEE', 'motif_rejet' => '  Pièce justificative invalide '])
            ->assertOk()
            ->assertJsonPath('data.statut', 'REJETEE')
            ->assertJsonPath('data.motif_rejet', 'Pièce justificative invalide');
    }

    public static function rejetsSansMotif(): array
    {
        return [
            'motif absent' => [[]],
            'motif vide' => [['motif_rejet' => '']],
            'motif blanc' => [['motif_rejet' => '   ']],
        ];
    }

    #[DataProvider('rejetsSansMotif')]
    public function test_rejet_sans_motif_interdit(array $motif): void
    {
        $demande = $this->demandeAvecStatut(Statut::EN_COURS);

        $this->changer($demande->id, ['statut' => 'REJETEE', ...$motif])
            ->assertStatus(400)
            ->assertJsonFragment(['Le motif de rejet est obligatoire.']);

        $this->assertSame(Statut::EN_COURS, $demande->refresh()->statut);
    }

    public function test_motif_refuse_pour_un_autre_statut_que_rejetee(): void
    {
        $demande = $this->demandeAvecStatut(Statut::EN_COURS);

        $this->changer($demande->id, ['statut' => 'VALIDEE', 'motif_rejet' => 'inutile'])
            ->assertStatus(400)
            ->assertJsonValidationErrors('motif_rejet');
    }

    public static function transitionsInterdites(): array
    {
        return [
            'DEPOSEE -> VALIDEE' => [Statut::DEPOSEE, ['statut' => 'VALIDEE']],
            'DEPOSEE -> REJETEE' => [Statut::DEPOSEE, ['statut' => 'REJETEE', 'motif_rejet' => 'x']],
            'EN_COURS -> DEPOSEE' => [Statut::EN_COURS, ['statut' => 'DEPOSEE']],
            'VALIDEE -> EN_COURS' => [Statut::VALIDEE, ['statut' => 'EN_COURS']],
            'VALIDEE -> REJETEE' => [Statut::VALIDEE, ['statut' => 'REJETEE', 'motif_rejet' => 'x']],
            'VALIDEE -> DEPOSEE' => [Statut::VALIDEE, ['statut' => 'DEPOSEE']],
            'REJETEE -> EN_COURS' => [Statut::REJETEE, ['statut' => 'EN_COURS']],
            'REJETEE -> VALIDEE' => [Statut::REJETEE, ['statut' => 'VALIDEE']],
            'REJETEE -> DEPOSEE' => [Statut::REJETEE, ['statut' => 'DEPOSEE']],
        ];
    }

    #[DataProvider('transitionsInterdites')]
    public function test_transition_interdite_renvoie_409(Statut $depuis, array $payload): void
    {
        $demande = $this->demandeAvecStatut($depuis);

        $this->changer($demande->id, $payload)
            ->assertStatus(409)
            ->assertExactJson([
                'statusCode' => 409,
                'message' => "Transition interdite de {$depuis->value} vers {$payload['statut']}.",
            ]);

        $this->assertSame($depuis, $demande->refresh()->statut);
    }

    public function test_statut_inconnu_refuse(): void
    {
        $demande = $this->demandeAvecStatut(Statut::DEPOSEE);

        $this->changer($demande->id, ['statut' => 'ARCHIVEE'])->assertStatus(400);
    }

    public function test_demande_inexistante_renvoie_404(): void
    {
        $this->changer('9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', ['statut' => 'EN_COURS'])
            ->assertNotFound()
            ->assertJsonPath('statusCode', 404);
    }

    // ---------- Consultation ----------

    public function test_recherche_par_npi_ne_retourne_que_ses_demandes(): void
    {
        $this->creer();
        $this->creer();
        $this->creer(['npi' => '9999999999']);

        $this->getJson('/api/usagers/'.self::NPI.'/demandes')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.npi', self::NPI)
            ->assertJsonPath('data.1.npi', self::NPI);
    }

    public function test_npi_invalide_dans_l_url_renvoie_400(): void
    {
        $this->getJson('/api/usagers/12ab/demandes')
            ->assertStatus(400)
            ->assertJsonPath('message.0', 'Le NPI doit contenir exactement 10 chiffres.');
    }

    public function test_filtre_par_statut(): void
    {
        $this->demandeAvecStatut(Statut::DEPOSEE);
        $this->demandeAvecStatut(Statut::EN_COURS);
        $this->demandeAvecStatut(Statut::EN_COURS);

        $this->getJson('/api/usagers/'.self::NPI.'/demandes?statut=EN_COURS')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.statut', 'EN_COURS')
            ->assertJsonPath('data.1.statut', 'EN_COURS');
    }

    public function test_filtre_statut_invalide_renvoie_400(): void
    {
        $this->getJson('/api/usagers/'.self::NPI.'/demandes?statut=INCONNU')->assertStatus(400);
    }

    public function test_tri_de_la_plus_recente_a_la_plus_ancienne(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');
        $ancienne = $this->creer()['id'];
        Carbon::setTestNow('2026-03-01 10:00:00');
        $recente = $this->creer()['id'];
        Carbon::setTestNow('2026-02-01 10:00:00');
        $milieu = $this->creer()['id'];
        Carbon::setTestNow();

        $ids = $this->getJson('/api/usagers/'.self::NPI.'/demandes')->json('data.*.id');

        $this->assertSame([$recente, $milieu, $ancienne], $ids);
    }

    public function test_pagination(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->demandeAvecStatut(Statut::DEPOSEE);
        }

        $this->getJson('/api/usagers/'.self::NPI.'/demandes?page=2&limit=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta', ['page' => 2, 'limit' => 10, 'total' => 25, 'total_pages' => 3]);

        $this->getJson('/api/usagers/'.self::NPI.'/demandes')
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.limit', 20)
            ->assertJsonPath('meta.total_pages', 2);
    }

    public function test_limite_maximale_de_20(): void
    {
        $this->getJson('/api/usagers/'.self::NPI.'/demandes?limit=21')
            ->assertStatus(400)
            ->assertJsonValidationErrors('limit');
    }

    public function test_liste_vide(): void
    {
        $this->getJson('/api/usagers/'.self::NPI.'/demandes')
            ->assertOk()
            ->assertExactJson(['data' => [], 'meta' => ['page' => 1, 'limit' => 20, 'total' => 0, 'total_pages' => 0]]);
    }

    // ---------- Statistiques ----------

    public function test_statistiques_toujours_les_quatre_statuts(): void
    {
        $this->getJson('/api/statistiques/statuts')
            ->assertOk()
            ->assertExactJson(['DEPOSEE' => 0, 'EN_COURS' => 0, 'VALIDEE' => 0, 'REJETEE' => 0]);
    }

    public function test_statistiques_par_statut(): void
    {
        $this->demandeAvecStatut(Statut::DEPOSEE);
        $this->demandeAvecStatut(Statut::DEPOSEE);
        $this->demandeAvecStatut(Statut::EN_COURS);
        $this->demandeAvecStatut(Statut::REJETEE, '5555555555');

        $this->getJson('/api/statistiques/statuts')
            ->assertOk()
            ->assertExactJson(['DEPOSEE' => 2, 'EN_COURS' => 1, 'VALIDEE' => 0, 'REJETEE' => 1]);
    }
}
