<?php

namespace Tests\Feature\Pieces;

use App\Models\Piece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavorisTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_cards_in_the_order_of_the_favourites(): void
    {
        config(['catalogue.seed.pieces' => 20, 'catalogue.seed.documents' => 0]);
        $this->seed();
        [$premiere, $seconde] = Piece::orderBy('id')->take(2)->pluck('reference')->all();

        $this->getJson("/favoris/pieces?references={$seconde},INCONNUE-01,{$premiere},<script>")
            ->assertOk()
            ->assertJsonCount(2, 'pieces')
            ->assertJsonPath('pieces.0.reference', $seconde)
            ->assertJsonPath('pieces.1.reference', $premiere)
            ->assertJsonMissingPath('pieces.0.description');
    }

    public function test_empty_list_returns_no_pieces(): void
    {
        $this->getJson('/favoris/pieces')->assertOk()->assertExactJson(['pieces' => []]);
    }

    public function test_favourites_page_renders(): void
    {
        $this->get('/favoris')->assertOk()->assertInertia(fn ($page) => $page->component('Favoris/Index'));
    }
}
