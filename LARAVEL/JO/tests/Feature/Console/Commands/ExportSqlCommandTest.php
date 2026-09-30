<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Event;
use App\Models\Result;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Tests\TestCase;

class ExportSqlCommandTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_export_can_be_reimported_into_an_empty_sqlite_database(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'jo');

        $this->artisan('jo:export-sql', ['--path' => $path])
            ->expectsOutputToContain('lignes exportées')
            ->assertSuccessful();

        $copy = new PDO('sqlite::memory:');
        $copy->exec(file_get_contents($path));
        unlink($path);

        $this->assertSame(Event::count(), (int) $copy->query('SELECT COUNT(*) FROM events')->fetchColumn());
        $this->assertSame(Result::count(), (int) $copy->query('SELECT COUNT(*) FROM results')->fetchColumn());
        $this->assertSame(Ticket::count(), (int) $copy->query('SELECT COUNT(*) FROM tickets')->fetchColumn());
        $this->assertSame(
            'Camille Spectatrice',
            $copy->query("SELECT name FROM users WHERE email = 'spectateur@jo.test'")->fetchColumn(),
        );
    }

    public function test_export_leaves_out_password_reset_tokens(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'jo');
        $this->app['db']->table('password_reset_tokens')->insert(['email' => 'spectateur@jo.test', 'token' => 'secret', 'created_at' => now()]);

        $this->artisan('jo:export-sql', ['--path' => $path])->assertSuccessful();

        $this->assertStringNotContainsString('secret', file_get_contents($path));
        unlink($path);
    }
}
