<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les adresses e-mail sont stockées et comparées en minuscules (SQLite compare en respectant la casse).
 * Une adresse qui n'est pas du texte (email[]=…) est vidée : la validation la refuse ensuite normalement.
 * Middleware global : il passe avant les limites de tentatives, qui comptent donc par adresse normalisée.
 */
class NormalizeEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('email')) {
            $email = $request->input('email');
            $request->merge(['email' => is_string($email) ? Str::lower($email) : null]);
        }

        return $next($request);
    }
}
