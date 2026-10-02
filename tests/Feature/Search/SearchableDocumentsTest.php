<?php

namespace Tests\Feature\Search;

use App\Models\Document;
use App\Models\Piece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchableDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalogue.seed.pieces' => 200, 'catalogue.seed.documents' => 10]);
        $this->seed();
    }

    public function test_piece_index_document_exposes_facet_slugs_and_display_labels(): void
    {
        $documentId = DB::table('document_piece')->value('document_id');
        $pieceId = DB::table('document_piece')->where('document_id', $documentId)->value('piece_id');

        $indexe = Piece::with(Piece::RELATIONS_INDEX)->findOrFail($pieceId)->toSearchableArray();

        $this->assertMatchesRegularExpression('/^1-\d+$/', $indexe['echelle']);
        $this->assertMatchesRegularExpression('/^1\/\d+$/', $indexe['echelle_libelle']);
        $this->assertContains($documentId, $indexe['document_ids']);
        $this->assertStringStartsWith('thumbnails/categories/', $indexe['miniature']);
        $this->assertIsFloat($indexe['longueur_mm']);
    }

    public function test_document_index_contains_plain_text_without_markup(): void
    {
        $indexe = Document::withCount('pieces')->firstOrFail()->toSearchableArray();

        $this->assertStringNotContainsString('<', $indexe['texte']);
        $this->assertLessThanOrEqual(223, mb_strlen($indexe['extrait']));
        $this->assertGreaterThan(0, $indexe['pieces_count']);
    }
}
