<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Piece;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use Illuminate\Support\Sleep;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Meilisearch\Contracts\TasksQuery;
use Meilisearch\Exceptions\ApiException;

/**
 * Installation complète en une commande : stockage, base, données de
 * démonstration, miniatures et index Meilisearch.
 */
#[Signature('app:install
    {--pieces= : Nombre de pièces à générer (2 000 par défaut, 300 000 pour le test de charge)}
    {--force : Recréer la base sans demander de confirmation}')]
#[Description('Installe le catalogue de démonstration : migrations, seeders, miniatures et indexation')]
class InstallCatalogue extends Command
{
    private const ATTENTE_INDEXATION_MAX = 1800;

    public function handle(EngineManager $moteurs): int
    {
        $pieces = $this->option('pieces');
        if ($pieces !== null && (! ctype_digit($pieces) || (int) $pieces < 1)) {
            $this->components->error('--pieces attend un entier positif.');

            return self::FAILURE;
        }

        if (! $this->option('force') && $this->baseContientDesDonnees()
            && ! $this->confirm('La base et les index existants seront effacés. Continuer ?')) {
            return self::FAILURE;
        }

        $debut = microtime(true);

        $this->components->info('1/5 Stockage S3');
        $this->call('app:storage-setup');

        $this->components->info('2/5 Base de données');
        $this->call('migrate:fresh', ['--force' => true]);

        $this->components->info('3/5 Données de démonstration');
        if ($pieces !== null) {
            config(['catalogue.seed.pieces' => (int) $pieces]);
        }
        $this->call('db:seed', ['--force' => true]);
        // Les libellés des facettes sont mis en cache par RechercheCatalogue.
        $this->call('cache:clear');

        $this->components->info('4/5 Miniatures');
        $this->call('app:thumbnails');

        $this->components->info('5/5 Indexation Meilisearch');
        /** @var MeilisearchEngine $meilisearch */
        $meilisearch = $moteurs->engine('meilisearch');
        // L'import se fait ici, par lots synchrones : aucun worker de file n'est requis.
        config(['scout.queue' => false]);

        foreach ([Piece::class, Document::class] as $modele) {
            try {
                $meilisearch->deleteIndex((new $modele)->searchableAs());
            } catch (ApiException) {
                // Index absent à la première installation.
            }
        }
        $this->call('scout:sync-index-settings');
        $this->call('scout:import', ['model' => Piece::class]);
        $this->call('scout:import', ['model' => Document::class]);

        if (! $this->attendreIndexation($meilisearch)) {
            $this->components->error('Meilisearch n’a pas fini d’indexer dans le délai imparti.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Pièces indexées', Number::format($meilisearch->index((new Piece)->searchableAs())->stats()['numberOfDocuments']));
        $this->components->twoColumnDetail('Documents indexés', Number::format($meilisearch->index((new Document)->searchableAs())->stats()['numberOfDocuments']));
        $this->components->twoColumnDetail('Durée totale', Number::format(microtime(true) - $debut, 1).' s');
        $this->components->twoColumnDetail('Compte admin', (string) config('catalogue.admin.email'));
        $this->newLine();
        $this->components->info('Catalogue prêt sur '.config('app.url'));

        return self::SUCCESS;
    }

    private function baseContientDesDonnees(): bool
    {
        return Schema::hasTable('pieces') && DB::table('pieces')->exists();
    }

    /**
     * Meilisearch indexe en tâche de fond : on attend la fin des tâches pour
     * que le catalogue soit consultable dès la fin de la commande.
     */
    private function attendreIndexation(MeilisearchEngine $meilisearch): bool
    {
        $requete = (new TasksQuery)->setStatuses(['enqueued', 'processing'])->setLimit(1);
        $limite = time() + self::ATTENTE_INDEXATION_MAX;

        while (($restantes = $meilisearch->getTasks($requete)->getTotal()) > 0) {
            if (time() > $limite) {
                return false;
            }
            $this->output->write("\r  Tâches Meilisearch en cours : {$restantes}   ");
            Sleep::for(1)->second();
        }
        $this->output->write("\r".str_repeat(' ', 50)."\r");

        return true;
    }
}
