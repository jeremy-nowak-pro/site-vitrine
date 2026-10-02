<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class ErreursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
        Route::get('/test-erreur', fn () => throw new RuntimeException('détail interne SQLSTATE'))->middleware('web');
    }

    public function test_missing_page_renders_the_inertia_error_page(): void
    {
        $this->get('/pieces/XXX-INCONNUE')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Erreur')->where('statut', 404));
    }

    public function test_server_error_never_exposes_the_exception(): void
    {
        $this->get('/test-erreur')
            ->assertStatus(500)
            ->assertDontSee('SQLSTATE')
            ->assertInertia(fn (Assert $page) => $page->component('Erreur')->where('statut', 500));
    }

    public function test_json_errors_are_generic(): void
    {
        $this->getJson('/test-erreur')
            ->assertStatus(500)
            ->assertExactJson(['error' => 'Une erreur est survenue']);
    }
}
