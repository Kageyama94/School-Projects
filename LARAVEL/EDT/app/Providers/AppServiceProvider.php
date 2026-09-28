<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // La file d'attente est en « sync » : inutile de lancer un worker avec `composer dev`.
        DevCommands::except('queue');
    }
}
