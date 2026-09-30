<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_FAILED_LOGINS = 5;

    public function register(Request $request): View
    {
        $this->rememberEvent($request);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', 'Bienvenue '.$user->name.' ! Votre compte a été créé.');
    }

    public function login(Request $request): View
    {
        $this->rememberEvent($request);

        return view('auth.login');
    }

    /**
     * Seuls les échecs comptent (5 par minute pour une adresse e-mail et une IP) : un compte de démo
     * partagé par toute une classe peut se connecter autant de fois que nécessaire.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $key = 'login-failures|'.$credentials['email'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_LOGINS)) {
            return back()->withErrors(['email' => trans('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => trans('auth.failed')])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Connexion depuis une épreuve (?epreuve=ID) : on y revient ensuite pour réserver. */
    private function rememberEvent(Request $request): void
    {
        if ($event = Event::find($request->integer('epreuve'))) {
            redirect()->setIntendedUrl(route('events.show', $event));
        }
    }
}
