<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_built_assets_are_committed_with_the_project(): void
    {
        $this->assertFileExists(public_path('css/app.css'));
        $this->assertFileExists(public_path('js/alpine.min.js'));
        $this->assertFileExists(public_path('js/teacher-scheduler.js'));
        $this->assertGreaterThan(10_000, filesize(public_path('css/app.css')));
    }

    public function test_pages_only_load_local_assets(): void
    {
        $admin = User::factory()->admin()->create();
        $pages = [
            $this->get('/'),
            $this->get(route('login')),
            $this->actingAs($admin)->get(route('dashboard')),
        ];

        foreach ($pages as $response) {
            $response->assertOk()
                ->assertSee(asset('css/app.css'), false)
                ->assertDontSee('cdn.tailwindcss.com')
                ->assertDontSee('unpkg.com')
                ->assertDontSee('fonts.bunny.net');
        }

        $pages[2]->assertSee(asset('js/alpine.min.js'), false);
    }

    public function test_pages_use_the_application_branding_instead_of_the_laravel_logo(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));

        $admin = User::factory()->admin()->create();
        $pages = [
            $this->get('/'),
            $this->get(route('login')),
            $this->actingAs($admin)->get(route('dashboard')),
        ];

        foreach ($pages as $response) {
            $response->assertOk()
                ->assertSee('<link rel="icon" type="image/svg+xml" href="'.asset('favicon.svg').'">', false)
                ->assertDontSee('M305.8 81.125', false);
        }

        $pages[1]->assertSee('aria-label="Emploi du temps"', false)->assertSee('EDT');
        $pages[2]->assertSee('aria-label="Emploi du temps"', false)->assertSee('>EDT</span>', false);
    }

    public function test_the_application_serves_no_files_from_the_local_disk(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_committed_stylesheet_is_identical_to_a_fresh_build(): void
    {
        $binary = collect(['tools/tailwindcss.exe', 'tools/tailwindcss'])
            ->map(fn (string $path) => base_path($path))
            ->first(fn (string $path) => is_file($path));

        if ($binary === null) {
            $this->markTestSkipped('CLI Tailwind absente du dossier tools/ (voir « Modifier l\'interface » dans le README).');
        }

        $output = storage_path('framework/testing/tailwind-check.css');
        @mkdir(dirname($output), 0777, true);

        $result = Process::path(base_path())->timeout(120)->run([
            $binary, '-c', 'tailwind.config.js', '-i', 'resources/css/app.css', '-o', $output, '--minify',
        ]);

        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertSame(
            file_get_contents($output),
            file_get_contents(public_path('css/app.css')),
            'public/css/app.css est périmé : relancer la commande de build du CSS (voir README).'
        );

        @unlink($output);
    }

    public function test_stylesheet_was_rebuilt_after_the_last_view_changes(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        foreach (['print\\:hidden', 'print\\:grid-cols-3', 'dark\\:bg-gray-800', 'lg\\:grid-cols-6', 'bg-amber-100', 'bg-red-50'] as $class) {
            $this->assertStringContainsString($class, $css, "La classe {$class} manque : relancer la commande de build du CSS (voir README).");
        }
    }
}
