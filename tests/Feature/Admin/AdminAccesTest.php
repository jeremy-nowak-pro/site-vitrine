<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pagesAdmin(): array
    {
        return [
            'statistiques' => ['/admin/statistiques'],
            'ajout de pièce' => ['/admin/pieces/nouvelle'],
            'import' => ['/admin/import'],
        ];
    }

    #[DataProvider('pagesAdmin')]
    public function test_guests_are_sent_to_the_login_page(string $url): void
    {
        $this->get($url)->assertRedirect('/admin/connexion');
    }

    #[DataProvider('pagesAdmin')]
    public function test_logged_in_users_without_admin_flag_are_forbidden(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public function test_admin_can_log_in_and_out(): void
    {
        $admin = $this->admin('mot-de-passe-solide');

        $this->post('/admin/connexion', ['email' => $admin->email, 'password' => 'mot-de-passe-solide'])
            ->assertRedirect('/admin/statistiques');
        $this->assertAuthenticatedAs($admin);

        $this->post('/admin/deconnexion')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_returns_a_generic_error(): void
    {
        $admin = $this->admin('mot-de-passe-solide');

        $this->from('/admin/connexion')
            ->post('/admin/connexion', ['email' => $admin->email, 'password' => 'faux'])
            ->assertRedirect('/admin/connexion')
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
        $this->post('/admin/connexion', ['email' => 'inconnu@demo.local', 'password' => 'faux'])
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $admin = $this->admin('mot-de-passe-solide');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/connexion', ['email' => $admin->email, 'password' => 'faux']);
        }

        $this->post('/admin/connexion', ['email' => $admin->email, 'password' => 'mot-de-passe-solide'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Trop de tentatives', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_no_write_route_exists_for_pieces_or_import(): void
    {
        $this->actingAs($this->admin('x'));

        $this->post('/admin/pieces/nouvelle')->assertMethodNotAllowed();
        $this->post('/admin/pieces')->assertNotFound();
        $this->post('/admin/import')->assertMethodNotAllowed();
    }

    private function admin(string $motDePasse): User
    {
        return User::unguarded(fn () => User::factory()->create(['password' => $motDePasse, 'est_admin' => true]));
    }
}
