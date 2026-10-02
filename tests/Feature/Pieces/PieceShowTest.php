<?php

namespace Tests\Feature\Pieces;

use App\Enums\TypeConsultation;
use App\Models\Consultation;
use App\Models\Piece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PieceShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalogue.seed.pieces' => 150, 'catalogue.seed.documents' => 10]);
        $this->seed();
    }

    public function test_piece_page_exposes_only_selected_fields(): void
    {
        $piece = Piece::has('compatibles')->has('documents')->firstOrFail();

        $this->get('/pieces/'.$piece->reference)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pieces/Show')
                ->where('piece.reference', $piece->reference)
                ->where('piece.dimensions.longueur', $piece->longueur_mm)
                ->where('piece.modele_3d', null)
                ->missing('piece.created_at')
                ->missing('piece.categorie_id')
                ->has('compatibles', $piece->compatibles()->count())
                ->has('compatibles.0', fn (Assert $carte) => $carte
                    ->hasAll(['id', 'reference', 'nom', 'categorie_nom', 'echelle_libelle', 'fabricant_nom', 'modele_nom', 'miniature']))
                ->has('documents', $piece->documents()->count()));
    }

    public function test_visit_is_recorded_as_a_consultation(): void
    {
        $this->withoutDefer();
        $piece = Piece::firstOrFail();

        $this->get('/pieces/'.$piece->reference)->assertOk();

        $this->assertDatabaseHas(Consultation::class, [
            'type' => TypeConsultation::Piece->value,
            'consultable_id' => $piece->id,
        ]);
    }

    public function test_unknown_reference_returns_404(): void
    {
        $this->get('/pieces/XXX-XX00-999999')->assertNotFound();
    }

    public function test_private_stl_is_streamed_as_a_download(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('stl/piece.stl', 'solid test');
        $piece = Piece::firstOrFail();
        $piece->update(['chemin_stl' => 'stl/piece.stl']);

        $this->get("/pieces/{$piece->reference}/fichier.stl")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename='.$piece->reference.'.stl')
            ->assertStreamedContent('solid test');
        $this->get("/pieces/{$piece->reference}/fichier.glb")->assertNotFound();
    }

    public function test_file_url_changes_when_the_file_is_replaced(): void
    {
        $piece = Piece::firstOrFail();
        $url = fn () => $this->get('/pieces/'.$piece->reference)->viewData('page')['props']['piece']['modele_3d'];

        $piece->update(['chemin_modele_3d' => 'models/piece-v1.glb']);
        $avant = $url();
        $piece->update(['chemin_modele_3d' => 'models/piece-v2.glb']);

        $this->assertStringContainsString('/fichier.glb?v=', $avant);
        $this->assertNotSame($avant, $url());
    }
}
