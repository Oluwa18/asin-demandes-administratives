# Suivi des demandes d'actes administratifs

Étude de cas technique ASIN 2026 (Développeur(se) junior(e)).

## Contexte

Les usagers déposent en ligne des demandes d'actes administratifs (acte de naissance, casier judiciaire, certificat de résidence). Un agent traite ensuite chaque demande jusqu'à sa validation ou son rejet.

## Fonctionnalités

**Socle obligatoire**
1. Déposer une demande (NPI, type d'acte, nombre de copies). Elle reçoit un identifiant (UUID) et le statut `DEPOSEE`.
2. Consulter les demandes d'un usager, de la plus récente à la plus ancienne, avec un filtre facultatif par statut.
3. Faire avancer une demande dans son cycle de vie (`DEPOSEE → EN_COURS → VALIDEE | REJETEE`).

**Bonus réalisés** (voir [Bonus réalisés](#bonus-réalisés))
- Pagination (20 demandes maximum par page)
- Nombre de demandes par statut
- Tests automatisés des règles de gestion
- Interface web (Vue 3) : tableau de bord, liste des demandes d'un usager, formulaire de dépôt

## Architecture

```
.
├── backend/                  API REST Laravel 12
│   ├── app/
│   │   ├── Enums/            Statut (avec les transitions), TypeActe
│   │   ├── Exceptions/       Exceptions métier (transition interdite, motif manquant)
│   │   ├── Http/
│   │   │   ├── Controllers/  DemandeController, StatistiqueController (minces)
│   │   │   └── Requests/     StoreDemandeRequest, UpdateStatutRequest, ListDemandesRequest
│   │   ├── Models/           Demande (Eloquent, UUID)
│   │   └── Services/         DemandeWorkflowService (toute la logique métier)
│   ├── bootstrap/app.php     Rendu JSON des erreurs (400 / 404 / 409, sans stack trace)
│   ├── config/cors.php       Origines autorisées via FRONTEND_URL
│   ├── database/migrations/  Table demandes
│   ├── docs/requests.http    Requêtes de test manuel
│   ├── routes/api.php        Routes de l'API
│   └── tests/                Unit (matrice des transitions) + Feature (API)
└── frontend/                 Vue 3 + Vite + Tailwind CSS (style TailAdmin)
    └── src/
        ├── views/            DashboardView, DemandesView, NouvelleDemandeView
        ├── components/       StatutBadge, AlertMessage
        ├── services/api.js   Appels API centralisés (axios)
        └── router/
```

Choix de conception :
- **Contrôleurs minces** : validation dans les Form Requests, logique métier dans `DemandeWorkflowService`.
- **Cycle de vie centralisé** dans l'enum `Statut` (`transitionsAutorisees()`, `peutPasserA()`), seule source de vérité.
- **Statut non assignable** : `statut` n'est pas dans `$fillable`, le client ne peut pas l'imposer à la création.
- **Concurrence** : la mise à jour du statut est conditionnée au statut lu (`WHERE id = ? AND statut = ?`), deux agents ne peuvent pas appliquer deux transitions contradictoires.
- **Index** sur `(npi, created_at)` (consultation triée par usager) et `statut` (filtre, statistiques).

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.2+, Laravel 12, Eloquent, Form Requests |
| Base de données | PostgreSQL (Supabase, utilisé uniquement comme base PostgreSQL) |
| Tests | PHPUnit 11 (SQLite en mémoire, aucune pollution de la base Supabase) |
| Frontend | Vue 3, Vite, Vue Router, Tailwind CSS 4, axios |

## Prérequis

- PHP ≥ 8.2 avec les extensions `pdo_pgsql` (base Supabase) et `pdo_sqlite` (tests)
- Composer 2
- Node.js ≥ 20 et npm
- Une base PostgreSQL (projet Supabase gratuit), **ou** SQLite pour une évaluation locale (voir [Démarrage rapide sans Supabase](#démarrage-rapide-sans-supabase))

> Sous XAMPP/Windows, activer `extension=pdo_pgsql` et `extension=pgsql` dans `php.ini` (lignes commentées par défaut).

## Installation

```bash
git clone <url-du-depot>
cd <dossier>
```

## Configuration

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Renseigner ensuite les variables `DB_*` dans `backend/.env` (voir ci-dessous).

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
```

## Variables d'environnement

**backend/.env**

| Variable | Description | Exemple |
|---|---|---|
| `DB_CONNECTION` | Pilote | `pgsql` |
| `DB_HOST` | Hôte Supabase (pooler) | `aws-0-eu-west-3.pooler.supabase.com` |
| `DB_PORT` | Port | `5432` |
| `DB_DATABASE` | Base | `postgres` |
| `DB_USERNAME` | Utilisateur | `postgres.<ref-projet>` |
| `DB_PASSWORD` | Mot de passe de la base | — |
| `DB_SSLMODE` | SSL | `require` |
| `FRONTEND_URL` | Origine(s) autorisée(s) par CORS, séparées par des virgules | `http://localhost:3000` |

Les valeurs se trouvent dans Supabase : *Project Settings → Database → Connection parameters* (choisir le **Session pooler** si la connexion directe IPv6 n'est pas disponible).

**frontend/.env**

| Variable | Description | Défaut |
|---|---|---|
| `VITE_API_URL` | URL de l'API, préfixe `/api` inclus | `http://localhost:8000/api` |

Aucun secret n'est versionné : les fichiers `.env` sont ignorés par Git.

## Démarrage rapide sans Supabase

- La démonstration principale utilise **PostgreSQL (Supabase)**.
- **SQLite est proposé uniquement comme solution de démarrage rapide** pour évaluer l'application en local sans identifiants Supabase. Le code, la migration et les règles métier sont identiques, seul le pilote de base change.
- Les tests automatisés utilisent déjà SQLite en mémoire (`phpunit.xml`) et ne nécessitent aucune de ces étapes.

Prérequis : l'extension PHP `pdo_sqlite` (activée par défaut avec XAMPP).

**1.** Préparer le backend (si ce n'est pas déjà fait) :

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

**2.** Dans `backend/.env`, remplacer `DB_CONNECTION=pgsql` par `DB_CONNECTION=sqlite` et **commenter les autres lignes `DB_*`** :

```dotenv
DB_CONNECTION=sqlite
# DB_HOST=...
# DB_PORT=...
# DB_DATABASE=...
# DB_USERNAME=...
# DB_PASSWORD=...
# DB_SSLMODE=...
```

Sans `DB_DATABASE`, Laravel utilise automatiquement le fichier `backend/database/database.sqlite`. La ligne `DB_DATABASE=postgres` doit impérativement être commentée, sinon Laravel chercherait un fichier nommé `postgres`. Pour utiliser un autre emplacement, indiquer un chemin **absolu** : `DB_DATABASE=C:/chemin/vers/database.sqlite`.

**3.** Créer le fichier de base s'il n'existe pas (il est ignoré par Git) :

```bash
touch database/database.sqlite
```

Sous PowerShell : `New-Item database\database.sqlite -ItemType File`

**4.** Appliquer la migration et lancer l'API :

```bash
php artisan config:clear
php artisan migrate
php artisan serve
```

L'API répond sur `http://localhost:8000/api`. Le frontend se lance ensuite normalement (voir [Lancement frontend](#lancement-frontend)), sans autre configuration.

Pour revenir à Supabase : remettre `DB_CONNECTION=pgsql`, décommenter et renseigner les variables `DB_*`, puis exécuter `php artisan config:clear` et `php artisan migrate`.

## Base de données

Une seule table `demandes` :

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | uuid | clé primaire |
| `npi` | char(10) | indexé avec `created_at` |
| `type_acte` | enum | `ACTE_NAISSANCE`, `CASIER_JUDICIAIRE`, `CERTIFICAT_RESIDENCE` |
| `nombre_copies` | smallint | 1 à 5 (validé côté API) |
| `statut` | enum | `DEPOSEE` (défaut), `EN_COURS`, `VALIDEE`, `REJETEE` ; indexé |
| `motif_rejet` | text, nullable | renseigné uniquement pour `REJETEE` |
| `created_at`, `updated_at` | timestamp | |

## Migrations

```bash
cd backend
php artisan migrate
```

## Lancement backend

```bash
cd backend
php artisan serve
```

L'API écoute sur `http://localhost:8000/api`.

## Lancement frontend

```bash
cd frontend
npm run dev
```

L'interface est disponible sur `http://localhost:3000`.

## Tests

```bash
cd backend
php artisan test
```

Les tests utilisent SQLite en mémoire (configuré dans `phpunit.xml`) : ils ne nécessitent pas Supabase et ne la polluent pas.

Couverture : création valide, statut initial `DEPOSEE` non imposable, identifiant unique, NPI trop court / trop long / avec lettres / absent, type d'acte invalide, copies = 0 / 6 / non entier, matrice complète des 16 transitions (unitaire), chaque transition interdite via l'API (409), `EN_COURS → VALIDEE`, `EN_COURS → REJETEE` avec motif, rejet sans motif (absent, vide, blanc), motif refusé hors rejet, statut inconnu, demande inexistante (404), recherche par NPI, NPI invalide dans l'URL, filtre par statut, tri décroissant, pagination, limite maximale de 20, liste vide, statistiques.

Build du frontend :

```bash
cd frontend
npm run build
```

## Documentation de l'API

Des requêtes prêtes à l'emploi sont fournies dans [`backend/docs/requests.http`](backend/docs/requests.http) (extension REST Client de VS Code ou client HTTP de PhpStorm).

> La documentation Swagger/OpenAPI n'a pas été générée faute de temps : l'API est décrite ci-dessous et dans `requests.http`.

## API

Préfixe : `/api`. Les champs JSON sont en `snake_case` (convention Laravel). Toujours envoyer `Accept: application/json`.

| Méthode | Route | Description | Succès |
|---|---|---|---|
| `POST` | `/api/demandes` | Déposer une demande | 201 |
| `GET` | `/api/demandes/{id}` | Consulter une demande | 200 |
| `GET` | `/api/usagers/{npi}/demandes` | Demandes d'un usager (`?statut=&page=&limit=`) | 200 |
| `PATCH` | `/api/demandes/{id}/statut` | Changer le statut | 200 |
| `GET` | `/api/statistiques/statuts` | Nombre de demandes par statut | 200 |

### POST /api/demandes

```json
{ "npi": "1234567890", "type_acte": "ACTE_NAISSANCE", "nombre_copies": 2 }
```

Réponse `201` :

```json
{
  "message": "Demande créée avec succès",
  "data": {
    "id": "0199b8c2-…",
    "npi": "1234567890",
    "type_acte": "ACTE_NAISSANCE",
    "nombre_copies": 2,
    "statut": "DEPOSEE",
    "motif_rejet": null,
    "created_at": "2026-10-06T10:00:00.000000Z",
    "updated_at": "2026-10-06T10:00:00.000000Z"
  }
}
```

### GET /api/usagers/{npi}/demandes?statut=EN_COURS&page=1&limit=20

- `statut` facultatif ; `page` ≥ 1 (défaut 1) ; `limit` entre 1 et 20 (défaut 20, au-delà : 400).
- Tri : `created_at` décroissant.

```json
{
  "data": [ { "id": "…", "statut": "EN_COURS", "…": "…" } ],
  "meta": { "page": 1, "limit": 20, "total": 1, "total_pages": 1 }
}
```

### PATCH /api/demandes/{id}/statut

```json
{ "statut": "EN_COURS" }
{ "statut": "VALIDEE" }
{ "statut": "REJETEE", "motif_rejet": "Pièce justificative invalide" }
```

Réponse `200` : `{ "message": "Statut mis à jour avec succès", "data": { … } }`

### GET /api/statistiques/statuts

```json
{ "DEPOSEE": 3, "EN_COURS": 1, "VALIDEE": 2, "REJETEE": 1 }
```

Les 4 clés sont toujours présentes.

### Erreurs

Format commun, sans stack trace :

| Cas | Code | Exemple de `message` |
|---|---|---|
| Donnée invalide (NPI, type, copies, statut, limit…) | 400 | `["Le NPI doit contenir exactement 10 chiffres."]` (+ `errors` par champ) |
| Rejet sans motif | 400 | `["Le motif de rejet est obligatoire."]` |
| Motif fourni pour un autre statut que `REJETEE` | 400 | `["Le motif de rejet ne peut être renseigné que pour le statut REJETEE."]` |
| Demande inexistante | 404 | `"Demande introuvable."` |
| Transition interdite | 409 | `"Transition interdite de DEPOSEE vers VALIDEE."` |

```json
{ "statusCode": 409, "message": "Transition interdite de DEPOSEE vers VALIDEE." }
```

## Règles métier

- **NPI** : obligatoire, exactement 10 chiffres (`^\d{10}$`).
- **Type d'acte** : `ACTE_NAISSANCE`, `CASIER_JUDICIAIRE` ou `CERTIFICAT_RESIDENCE`.
- **Nombre de copies** : entier de 1 à 5.
- **Statut initial** : toujours `DEPOSEE`, même si le client envoie un autre statut (champ ignoré).
- **Rejet** : un motif non vide est obligatoire (les espaces sont retirés).

## Cycle de vie

```
DEPOSEE ──► EN_COURS ──► VALIDEE  (final)
                    └──► REJETEE  (final, motif obligatoire)
```

Toute autre transition (retour arrière, saut d'étape, modification d'un état final) est refusée avec un `409 Conflict`.

## Frontend

- **Tableau de bord** : 4 cartes (Déposées, En cours, Validées, Rejetées) alimentées par `/api/statistiques/statuts`.
- **Demandes** : recherche par NPI, filtre par statut, tableau (référence, NPI, type, copies, badge de statut, motif, date, actions), pagination.
  - `DEPOSEE` : bouton *Passer en cours* ; `EN_COURS` : *Valider* / *Rejeter* (modale de saisie du motif) ; `VALIDEE` / `REJETEE` : aucune action.
- **Nouvelle demande** : formulaire NPI / type d'acte / copies (1 à 5), messages de chargement, succès (lien vers les demandes de l'usager) et erreur.

## Scénario de démonstration

Avec l'API démarrée (`php artisan serve`) et le frontend (`npm run dev`), ou avec `backend/docs/requests.http` :

1. *Nouvelle demande* : NPI `1234567890`, Acte de naissance, 2 copies → message de succès.
2. La demande est au statut `DEPOSEE`.
3. *Demandes* : rechercher `1234567890` → la demande apparaît en tête.
4. Cliquer *Passer en cours* → `EN_COURS`.
5. Cliquer *Valider* → `VALIDEE`, plus aucune action proposée.
6. Via l'API, `PATCH … { "statut": "EN_COURS" }` sur cette demande → `409 Transition interdite de VALIDEE vers EN_COURS.`
7. Créer une deuxième demande pour le même NPI.
8. *Passer en cours*.
9. *Rejeter* → saisir « Pièce justificative invalide » → `REJETEE`, motif affiché.
10. Via l'API, rejet sans motif sur une demande `EN_COURS` → `400 Le motif de rejet est obligatoire.`
11. NPI `12ab` → `400 Le NPI doit contenir exactement 10 chiffres.`
12. Type `PASSEPORT` → `400`.
13. `nombre_copies: 6` → `400`.
14. *Tableau de bord* : les compteurs reflètent les demandes créées.

## Bonus réalisés

- [x] Pagination, 20 demandes maximum par page (`limit > 20` refusé)
- [x] Nombre de demandes par statut (`GET /api/statistiques/statuts`, carte du tableau de bord)
- [x] Tests automatisés des règles de gestion (PHPUnit, unitaires + fonctionnels)
- [x] Écran listant les demandes d'un usager (+ tableau de bord et formulaire de dépôt)

## Déploiement

Non réalisé dans le temps imparti. L'application se lance localement en suivant ce README.

## Limites éventuelles

- Pas de documentation Swagger/OpenAPI générée (remplacée par ce README et `requests.http`).
- Pas d'authentification ni de gestion des rôles (hors périmètre du sujet).
- Pas d'historique des changements de statut (seul le statut courant et `updated_at` sont conservés).
- Les tests tournent sur SQLite en mémoire ; le comportement PostgreSQL est équivalent pour les requêtes utilisées (Eloquent), mais n'est pas testé automatiquement.
