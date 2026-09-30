@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
<section class="wrap auth">
    <div class="card auth-card">
        @include('partials.rings', ['size' => 70])
        <h1>Connexion</h1>
        <form method="POST" action="{{ route('login') }}" class="form">
            @csrf
            <x-field name="email" label="Adresse e-mail">
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </x-field>
            <x-field name="password" label="Mot de passe">
                <input type="password" name="password" required autocomplete="current-password">
            </x-field>
            <div class="form-row">
                <label class="checkbox">
                    <input type="checkbox" name="remember"> Se souvenir de moi
                </label>
                <a href="{{ route('password.request') }}" class="small">Mot de passe oublié ?</a>
            </div>
            <button class="btn btn-primary btn-block">Se connecter</button>
        </form>
        <p class="muted small">Pas encore de compte ? <a href="{{ route('register', request()->only('epreuve')) }}">Inscrivez-vous</a></p>
    </div>
</section>
@endsection
