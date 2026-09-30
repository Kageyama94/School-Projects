<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Pages d'erreur en français, aux couleurs du site. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_not_found_pages_are_in_french(): void
    {
        $this->get('/nimporte-quoi')->assertNotFound()->assertSee('Page introuvable')->assertDontSee('Not Found');
        $this->get('/epreuves/99999')->assertNotFound()->assertSee('Page introuvable')->assertSee('Voir les épreuves');
    }

    public function test_forbidden_page_is_in_french(): void
    {
        $this->actingAs($this->spectator())
            ->get('/admin')->assertForbidden()->assertSee('Accès refusé')->assertDontSee('Forbidden');
    }

    public function test_server_error_page_is_in_french(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test-erreur', fn () => throw new \RuntimeException('boum'));

        $this->get('/_test-erreur')->assertStatus(500)->assertSee('Erreur du serveur')->assertDontSee('boum');
    }

    public function test_other_client_errors_use_the_generic_page(): void
    {
        $this->post('/sites')->assertStatus(405)->assertSee('Requête impossible')->assertSee('405');
    }

    public function test_expired_throttled_and_maintenance_pages_exist(): void
    {
        $this->assertStringContainsString('Page expirée', view('errors.419')->render());
        $this->assertStringContainsString('Trop de tentatives', view('errors.429')->render());
        $this->assertStringContainsString('maintenance', view('errors.503')->render());
    }
}
