<?php

namespace App\Providers;

use App\Support\Format;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::resourceVerbs(['create' => 'creer', 'edit' => 'modifier']);

        $this->configureModels();
        $this->configureRateLimiting();
        $this->registerBladeDirectives();
    }

    /**
     * Hors production, une requête N+1 ou un attribut ignoré par $fillable lèvent une exception
     * au lieu de passer inaperçus.
     */
    private function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }

    /**
     * « forms » (inscription, mot de passe oublié) : 5 envois par minute pour une même adresse e-mail
     * sur un même formulaire. « login » : seulement le plafond par IP, car les échecs par adresse
     * e-mail sont comptés dans AuthController (les connexions réussies ne sont pas limitées).
     * Plafond de 60 par minute et par IP, car toute une classe partage souvent la même IP.
     * Le message s'affiche sur le formulaire plutôt que sur une page d'erreur.
     */
    private function configureRateLimiting(): void
    {
        $tooMany = fn (Request $request, array $headers): RedirectResponse => back()
            ->withErrors(['email' => trans('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60])])
            ->onlyInput('email');

        RateLimiter::for('forms', function (Request $request) use ($tooMany): array {
            $email = (string) $request->input('email'); // déjà en minuscules (middleware NormalizeEmail)

            return [
                Limit::perMinute(5)->by($request->path().'|'.$email.'|'.$request->ip())->response($tooMany),
                Limit::perMinute(60)->by($request->path().'|'.$request->ip())->response($tooMany),
            ];
        });

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(60)
            ->by('login|'.$request->ip())
            ->response($tooMany));
    }

    /**
     * @number(1234) → « 1 234 », @euros(90) → « 90 € », @percent(0.42) → « 42 % ».
     */
    private function registerBladeDirectives(): void
    {
        foreach (['number', 'euros', 'percent'] as $format) {
            Blade::directive($format, fn (string $expression): string => '<?php echo e('.Format::class."::$format($expression)); ?>");
        }
    }
}
