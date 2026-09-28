<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test-error/{code}', fn (int $code) => abort($code));
    }

    public function test_forbidden_page_is_in_french(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.teachers.index'))
            ->assertForbidden()
            ->assertSee('Accès interdit')
            ->assertDontSee('Forbidden');
    }

    public function test_not_found_page_is_in_french(): void
    {
        $this->get('/cette-page-n-existe-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertDontSee('Not Found');
    }

    public function test_page_expired_explains_what_to_do(): void
    {
        $this->get('/_test-error/419')
            ->assertStatus(419)
            ->assertSee('419')
            ->assertSee('Page expirée')
            ->assertSee('Ta session a expiré')
            ->assertDontSee('Page Expired');
    }

    public function test_other_framework_error_pages_are_translated(): void
    {
        $expected = [
            401 => 'Non autorisé',
            429 => 'Trop de requêtes',
            500 => 'Erreur du serveur',
            503 => 'Service indisponible',
        ];

        foreach ($expected as $code => $message) {
            $this->get("/_test-error/{$code}")->assertStatus($code)->assertSee($message);
        }
    }
}
