<?php

namespace Tests\Feature\Seeders;

use App\Models\Document;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogueSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalogue.seed.pieces' => 400,
            'catalogue.seed.documents' => 20,
            'catalogue.admin.email' => 'admin@test.local',
            'catalogue.admin.password' => 'secret-de-test',
        ]);

        $this->seed();
    }

    public function test_it_generates_the_requested_volume(): void
    {
        $this->assertSame(400, Piece::count());
        $this->assertSame(20, Document::count());
        $this->assertTrue(User::where('email', 'admin@test.local')->exists());
    }

    public function test_compatibilities_are_symmetric_and_share_model_and_scale(): void
    {
        $asymetriques = DB::table('piece_compatibilite as a')
            ->leftJoin('piece_compatibilite as b', fn ($join) => $join
                ->on('b.piece_id', '=', 'a.compatible_id')
                ->on('b.compatible_id', '=', 'a.piece_id'))
            ->whereNull('b.piece_id')
            ->count();

        $incoherentes = DB::table('piece_compatibilite as c')
            ->join('pieces as p', 'p.id', '=', 'c.piece_id')
            ->join('pieces as q', 'q.id', '=', 'c.compatible_id')
            ->where(fn ($query) => $query
                ->whereColumn('p.modele_id', '!=', 'q.modele_id')
                ->orWhereColumn('p.echelle_id', '!=', 'q.echelle_id')
                ->orWhereColumn('p.categorie_id', '=', 'q.categorie_id'))
            ->count();

        $this->assertGreaterThan(0, DB::table('piece_compatibilite')->count());
        $this->assertSame(0, $asymetriques);
        $this->assertSame(0, $incoherentes);
    }

    public function test_every_document_is_linked_to_at_least_one_piece(): void
    {
        $this->assertSame(0, Document::doesntHave('pieces')->count());
    }

    public function test_piece_sequence_continues_after_seeding(): void
    {
        $copie = Piece::findOrFail(1)->replicate();
        $copie->reference = 'TEST-0001';
        $copie->save();

        $this->assertSame(401, $copie->id);
    }
}
