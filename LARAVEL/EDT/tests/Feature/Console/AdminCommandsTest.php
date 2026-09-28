<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_be_created_with_options(): void
    {
        $this->artisan('app:create-admin', [
            '--name' => 'Direction',
            '--identifiant' => '1',
            '--password' => 'motdepasse1',
        ])->expectsOutputToContain('Identifiant : 1')->assertSuccessful();

        $admin = User::where('identifiant', '1')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame('Direction', $admin->name);
        $this->assertFalse($admin->must_change_password);
        $this->assertTrue(Hash::check('motdepasse1', $admin->password));
    }

    public function test_an_admin_can_be_created_interactively_with_a_suggested_identifiant(): void
    {
        User::factory()->create(['identifiant' => '1']);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Nom de l\'administrateur', 'Direction')
            ->expectsQuestion('Identifiant de connexion', '2')
            ->expectsQuestion('Mot de passe (8 caractères minimum, lettres et chiffres)', 'motdepasse1')
            ->expectsQuestion('Confirmer le mot de passe', 'motdepasse1')
            ->assertSuccessful();

        $this->assertTrue(User::where('identifiant', '2')->firstOrFail()->isAdmin());
    }

    public function test_creation_is_refused_for_invalid_input(): void
    {
        User::factory()->create(['identifiant' => '1']);

        $this->artisan('app:create-admin', ['--name' => 'A', '--identifiant' => '1', '--password' => 'motdepasse1'])->assertFailed();
        $this->artisan('app:create-admin', ['--name' => 'A', '--identifiant' => 'abc', '--password' => 'motdepasse1'])->assertFailed();
        $this->artisan('app:create-admin', ['--name' => 'A', '--identifiant' => '123456789', '--password' => 'motdepasse1'])->assertFailed();
        $this->artisan('app:create-admin', ['--name' => 'A', '--identifiant' => '5', '--password' => 'court'])->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_mismatching_password_confirmation_is_refused(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'A', '--identifiant' => '5'])
            ->expectsQuestion('Mot de passe (8 caractères minimum, lettres et chiffres)', 'motdepasse1')
            ->expectsQuestion('Confirmer le mot de passe', 'autre-chose1')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_an_admin_password_can_be_reset_and_sessions_are_closed(): void
    {
        $admin = User::factory()->admin()->create(['identifiant' => '1', 'must_change_password' => true]);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);

        $this->artisan('app:reset-admin-password', ['identifiant' => '1', '--password' => 'nouveaumdp1'])->assertSuccessful();

        $admin->refresh();
        $this->assertTrue(Hash::check('nouveaumdp1', $admin->password));
        $this->assertFalse($admin->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_reset_is_refused_for_unknown_or_non_admin_accounts(): void
    {
        $student = User::factory()->student()->create(['identifiant' => '94020000']);

        $this->artisan('app:reset-admin-password', ['identifiant' => '999', '--password' => 'nouveaumdp1'])->assertFailed();
        $this->artisan('app:reset-admin-password', ['identifiant' => '94020000', '--password' => 'nouveaumdp1'])->assertFailed();

        $this->assertTrue(Hash::check('password', $student->fresh()->password));
    }

    public function test_reset_refuses_a_weak_password(): void
    {
        $admin = User::factory()->admin()->create(['identifiant' => '1']);

        $this->artisan('app:reset-admin-password', ['identifiant' => '1', '--password' => 'court'])->assertFailed();

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }
}
