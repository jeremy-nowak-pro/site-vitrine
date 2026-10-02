<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalogue.seed.pieces' => 100, 'catalogue.seed.documents' => 10, 'catalogue.admin.email' => 'admin@test.local', 'catalogue.admin.password' => 'secret']);
        $this->seed();
        $this->actingAs(User::where('email', 'admin@test.local')->firstOrFail());
    }

    public function test_statistics_are_fictitious_but_stable_for_the_day(): void
    {
        $premiere = $this->get('/admin/statistiques')->assertOk()->viewData('page')['props'];
        $seconde = $this->get('/admin/statistiques')->viewData('page')['props'];

        $this->assertSame($premiere['serie'], $seconde['serie']);
        $this->assertCount(30, $premiere['serie']);
        $this->assertCount(4, $premiere['indicateurs']);
        $this->assertCount(10, $premiere['piecesPopulaires']);
        $this->assertSame(
            array_sum(array_column($premiere['serie'], 'pieces')),
            $premiere['indicateurs'][0]['valeur'],
        );
    }

    public function test_piece_form_lists_every_reference_table_and_document(): void
    {
        $this->get('/admin/pieces/nouvelle')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pieces/Create')
                ->has('options.categorie', 8)
                ->has('options.echelle', 7)
                ->has('options.modele', 28)
                ->has('options.fabricant', 12)
                ->has('options.materiau', 7)
                ->has('options.periode', 5)
                ->has('documents', 10));
    }

    public function test_import_page_describes_the_expected_columns(): void
    {
        $this->get('/admin/import')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Import')
                ->where('colonnes.0.nom', 'reference')
                ->where('colonnes.0.obligatoire', true));
    }

    public function test_shared_auth_props_expose_only_name_and_email(): void
    {
        $this->get('/admin/import')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.email', 'admin@test.local')
                ->missing('auth.password')
                ->missing('auth.est_admin'));
    }
}
