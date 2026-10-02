<?php

namespace Tests\Concerns;

use App\Models\Document;
use App\Models\Piece;
use Illuminate\Support\Sleep;
use Laravel\Scout\EngineManager;
use Meilisearch\Contracts\TasksQuery;
use Meilisearch\Exceptions\ApiException;

/**
 * Indexe les données de test dans le Meilisearch de Sail, sur des index
 * préfixés « test_ » pour ne pas toucher aux données de démonstration.
 */
trait IndexeMeilisearch
{
    protected function indexerPourLesTests(int $pieces, int $documents): void
    {
        config([
            'scout.driver' => 'meilisearch',
            'scout.prefix' => 'test_',
            'scout.queue' => false,
            'catalogue.seed.pieces' => $pieces,
            'catalogue.seed.documents' => $documents,
        ]);
        $this->seed();
        $this->supprimerIndexDeTest();
        $this->artisan('scout:sync-index-settings');
        Piece::makeAllSearchable();
        Document::makeAllSearchable();
        $this->attendreIndexation();
    }

    protected function supprimerIndexDeTest(): void
    {
        $meilisearch = app(EngineManager::class)->engine('meilisearch');
        foreach (['test_pieces', 'test_documents'] as $index) {
            try {
                $meilisearch->deleteIndex($index);
            } catch (ApiException) {
                // Index absent.
            }
        }
        $this->attendreIndexation();
    }

    private function attendreIndexation(): void
    {
        $meilisearch = app(EngineManager::class)->engine('meilisearch');
        $requete = (new TasksQuery)->setStatuses(['enqueued', 'processing'])->setLimit(1);
        for ($essai = 0; $essai < 100 && $meilisearch->getTasks($requete)->getTotal() > 0; $essai++) {
            Sleep::for(100)->milliseconds();
        }
    }
}
