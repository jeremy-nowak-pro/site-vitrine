# Catalogue Miniatures

Démo d'un catalogue de pièces de modélisme automobile : recherche à facettes, fiche pièce avec visionneuse 3D, favoris sans compte, documentation interne consultable en ligne et panel d'administration en lecture seule. Le jeu de données par défaut compte 2 000 pièces ; l'architecture a été testée avec 300 000.

Tous les fabricants, modèles de voitures, auteurs et chiffres sont fictifs.

## Accès

| | |
|---|---|
| Site public | http://localhost:8080 |
| Administration | http://localhost:8080/admin |
| Compte admin de démo | `admin@demo.local` / `catalogue-demo` |
| Meilisearch | http://localhost:7700 |
| Console du stockage S3 (RustFS) | http://localhost:9001 (`sail` / `password`) |

Les identifiants admin viennent de `DEMO_ADMIN_EMAIL` et `DEMO_ADMIN_PASSWORD` dans `.env`.

## Installation

Prérequis : Docker. PHP et Node ne sont pas nécessaires sur la machine.

```bash
cp .env.example .env

# Dépendances PHP, via un conteneur jetable (vendor/bin/sail n'existe pas encore)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php85-composer:latest composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan app:install
```

`app:install` enchaîne les étapes suivantes, en moins d’une minute au premier lancement (11 s quand le cache npm est déjà rempli) :

1. génération de `APP_KEY` si elle est vide ;
2. `npm ci` et `npm run build` si `public/build` n'existe pas (`--build` pour forcer) ;
3. création du bucket S3 et de sa politique d'accès ;
4. `migrate:fresh` ;
5. seeders : compte admin, tables de référence, 2 000 pièces, 60 documents ;
6. dépôt des miniatures sur le stockage S3 ;
7. réglages des index Meilisearch, indexation par lots de 2 000, puis attente de la fin de l'indexation.

La commande demande confirmation si la base contient déjà des données (`--force` pour l'éviter).

### Test de charge

```bash
./vendor/bin/sail artisan app:install --pieces=300000 --force
```

Environ 95 secondes sur un Mac M-series : 47 s de génération en base, le reste en indexation.

### Ports

L'application écoute sur 8080 et PostgreSQL est exposé sur 54320 pour éviter les conflits avec un Apache ou un PostgreSQL locaux. Les variables `APP_PORT` et `FORWARD_DB_PORT` de `.env` les changent.

### Commandes utiles

```bash
./vendor/bin/sail artisan test        # 48 tests, dont l'intégration Meilisearch (index préfixés test_)
./vendor/bin/sail npm run dev         # Vite en mode développement (rechargement à chaud)
./vendor/bin/sail npm run types       # vérification TypeScript
./vendor/bin/sail pint                # formatage PHP
```

## Architecture

### Stack

Laravel 13, Inertia 3, React 19, TypeScript, Vite 8, Tailwind 4, PostgreSQL 18, Meilisearch, Redis, stockage S3 local, Three.js via React Three Fiber.

Le stockage local est **RustFS** et non MinIO : Laravel Sail 1.68 ne propose plus MinIO, dont l'image Docker communautaire n'est plus distribuée. RustFS est compatible S3 ; Laravel l'utilise via le disque `s3` standard. Revenir à MinIO ne touche que `compose.yaml` et `.env`.

### Dépendances ajoutées au squelette Laravel

| Paquet | Raison |
|---|---|
| `inertiajs/inertia-laravel`, `@inertiajs/react` | Imposés. Les pages sont résolues par `import.meta.glob`, sans le plugin Vite d'Inertia. |
| `laravel/scout`, `meilisearch/meilisearch-php` | Imposés. Le client PSR-17 est fourni par Guzzle, déjà présent : `http-interop/http-factory-guzzle` n'a pas été nécessaire. |
| `league/flysystem-aws-s3-v3` | Pilote du disque `s3` de Laravel. |
| `react`, `react-dom`, `@vitejs/plugin-react`, `typescript`, `@types/react(-dom)` | Imposés. |
| `three`, `@react-three/fiber`, `@react-three/drei`, `@types/three` | Imposés. L'export STL utilise le `STLExporter` livré avec three. |
| `laravel/sail` (dev) | Imposé. |
| `laravel/boost` (dev) | Règles et serveur MCP pour l'assistant de développement. N'est pas chargé en production. |

Écrits à la main plutôt qu'importés : les graphiques SVG de l'admin, la zone de dépôt CSV, les favoris (`localStorage`), le nettoyeur HTML (extension DOM de PHP) et les styles de lecture des documents (pas de plugin Typography).

### Arborescence utile

```
app/
  Search/RechercheCatalogue.php      multi-search Meilisearch, facettes disjonctives
  Search/FiltresCatalogue.php        état du catalogue lu depuis l'URL, validé
  Search/RechercheDocumentation.php
  Support/ContenuHtml.php            nettoyage du HTML des documents (liste blanche)
  Support/MiniatureCategorie.php     miniatures SVG de démo
  Admin/StatistiquesFictives.php
  Console/Commands/InstallCatalogue.php
database/seeders/                    génération par lots, sans Eloquent ni Faker
resources/js/
  pages/                             une page Inertia par écran
  components/visionneuse/            visionneuse 3D, chargée à la demande
  hooks/useFavoris.ts
docker/serveur-local.php             routeur du serveur PHP de Sail (cache des assets)
```

### Données

- `pieces` référence six tables (`categories`, `echelles`, `fabricants`, `materiaux`, `periodes`, `modeles`). PostgreSQL n'indexe pas les clés étrangères : chaque `*_id` a son index, plus `(modele_id, echelle_id)`.
- `piece_compatibilite` est stockée dans les deux sens ; `document_piece` relie documents et pièces.
- `consultations (type, consultable_id, consulte_le)` reçoit une ligne par fiche ou document consulté, écrite après l'envoi de la réponse (`defer`). Index `(type, consulte_le, consultable_id)` pour les classements.
- Les seeders génèrent des données cohérentes : dimensions d'une pièce réelle divisées par l'échelle, compatibilités entre pièces du même modèle de voiture et de la même échelle, documents rattachés aux pièces qu'ils concernent.

### Recherche

Chaque pièce est indexée sous une forme dénormalisée : slugs des filtres (valeurs de l'URL), libellés d'affichage, `document_ids`. La page catalogue n'interroge pas PostgreSQL : les cartes viennent directement de l'index.

Les facettes sont disjonctives : une seule requête multi-search contient la recherche principale et une requête par facette active, sans son propre filtre. Une facette affiche ainsi le nombre de résultats qu'ajouterait chacune de ses valeurs.

Meilisearch limite la pagination à `maxTotalHits` (10 000). Le total affiché reste exact : il est calculé par la somme de la facette catégorie, chaque pièce ayant une catégorie. Au-delà de 416 pages, l'interface invite à affiner.

### Visionneuse 3D

- Chargée par `React.lazy` sur la fiche pièce uniquement : 283 Ko compressés, absents du catalogue.
- Unité de scène : 1 mm. Sans GLB, une forme procédurale par catégorie est construite aux dimensions de la pièce, découpée en sous-parties nommées (jante, pneu, écrou central ; bloc, culasse, carburateur, filtre à air ; etc.).
- Un GLB est mis à l'échelle sur la plus grande dimension de la pièce, quelles que soient ses unités d'export. Chaque maillage devient une sous-partie de la vue éclatée.
- Mesure par raycasting entre deux points cliqués ; grille au pas de 1, 2 ou 5 × 10ⁿ mm selon la taille de la pièce ; cotes en 3D.
- Le STL est un fichier fourni s'il existe, sinon il est généré depuis le modèle affiché.

### Sécurité

- Admin : sessions Laravel, limite de 5 tentatives par minute (e-mail + IP), message d'erreur identique pour un e-mail inconnu, Gate `acceder-admin` sur chaque route en plus de l'authentification. `est_admin` n'est pas assignable en masse.
- Aucune route d'écriture côté admin : l'ajout de pièce et l'import sont des formulaires désactivés.
- Les fichiers GLB et STL restent privés sur S3 et passent par l'application. Seul le préfixe `thumbnails/` est public.
- Le HTML des documents est filtré à l'affichage : liste blanche de balises, tous les attributs retirés sauf `scope`.
- Les ressources API sélectionnent explicitement leurs champs ; aucun modèle brut n'est envoyé au navigateur.
- Hors mode debug, les erreurs affichent une page générique ou `{ "error": "Une erreur est survenue" }`, jamais de trace.

### Cache HTTP

| Ressource | En-tête |
|---|---|
| `/build/assets/*` (nom avec empreinte) | `public, max-age=31536000, immutable` |
| Miniatures sur S3 (nom versionné `-v1`) | `public, max-age=31536000, immutable` |
| GLB et STL servis par l'application | `public, max-age=86400` |
| Pages HTML | `no-cache, private` (Laravel) |

En local, le serveur PHP de Sail passe par `docker/serveur-local.php` pour poser l'en-tête sur `/build/assets`. En production, la règle se place dans le serveur web :

```nginx
location /build/assets/ {
    add_header Cache-Control "public, max-age=31536000, immutable";
    try_files $uri =404;
}
```

## Performance mesurée

Jeu de 300 000 pièces et 1,8 million de liens de compatibilité, sur Sail (serveur PHP intégré, 4 workers, Mac M-series). Temps de réponse complets côté client, médiane de 15 appels au format des navigations Inertia :

| Requête | Médiane | Max |
|---|---|---|
| Catalogue sans filtre, 6 facettes | 26 ms | 42 ms |
| Recherche « volant » | 25 ms | 28 ms |
| Recherche + 3 facettes multi-valeurs + tri | 23 ms | 29 ms |
| Catalogue filtré par document | 26 ms | 30 ms |
| Page 300 d'une échelle (pagination profonde) | 116 ms | 159 ms |
| Fiche pièce (compatibles, documents) | 28 ms | 32 ms |
| Documentation, recherche | 17 ms | 21 ms |
| Lecture d'un document | 33 ms | 44 ms |
| Favoris : 200 références | 43 ms | |

Charge : 200 recherches aléatoires, 8 en parallèle, sans erreur. Médiane 55 ms, p95 115 ms, 100 requêtes par seconde. Le serveur PHP intégré limite ce débit ; nginx et PHP-FPM ou Octane le dépasseront en production.

Génération des 300 000 pièces : 47 s. Installation complète avec indexation : 95 s. Index Meilisearch : 750 Mo.

## Mise en production : ce qu'il faut remplacer

**Modèles 3D réels**

- Renseigner `chemin_modele_3d` (et éventuellement `chemin_stl`, `chemin_miniature`) avec des clés du disque S3 ; la visionneuse charge alors le GLB.
- Les décodeurs Draco et Meshopt sont désactivés dans `Visionneuse.tsx` : drei les téléchargerait depuis un CDN externe. Si les GLB sont compressés, copier les décodeurs dans `public/` et les déclarer avec `useGLTF.setDecoderPath`.
- Versionner les noms de fichiers (`-v2.glb`) pour profiter du cache.

**Activer l'administration**

- Ajouter les routes `POST /admin/pieces` et `POST /admin/import`, avec des Form Requests (référence unique, slugs existants, types et tailles de fichiers).
- Brancher l'import sur une file Redis : un job par lot de lignes, un worker (`php artisan queue:work`) supervisé.
- Passer `SCOUT_QUEUE=true` pour que l'indexation des modifications passe par la file.
- Nettoyer le HTML des documents à l'enregistrement aussi, pas seulement à l'affichage.
- Remplacer `StatistiquesFictives` par des requêtes sur `consultations`, idéalement agrégées chaque nuit dans une table de synthèse.
- Retirer le bandeau de démonstration (`BandeauDemo` dans `AdminLayout.tsx`) et l'étiquette « Données fictives ».
- Supprimer le compte de démo et créer les comptes réels avec `est_admin = true`.

**Infrastructure**

- `APP_ENV=production`, `APP_DEBUG=false`, `php artisan optimize`.
- Définir `MEILISEARCH_KEY` (clé maître de Meilisearch) et ne pas exposer le port 7700.
- Pointer `AWS_*` vers le vrai stockage S3 ; `AWS_URL` doit être l'URL publique du bucket (ou d'un CDN) pour les miniatures.
- Servir l'application par nginx + PHP-FPM (ou Octane) avec la règle de cache ci-dessus.

## Limites connues

- Un document est rattaché à 40 pièces au plus dans le jeu de démonstration ; à 300 000 pièces, la documentation ne couvre qu'une petite partie du catalogue.
- La visionneuse n'a pas de tests automatisés JavaScript (pas de Vitest, pour limiter les dépendances) ; elle a été vérifiée dans Chrome par script (formes, mesure, vue éclatée, export STL, GLB de test).
- La console affiche un avertissement de dépréciation `THREE.Clock` qui vient de React Three Fiber.
