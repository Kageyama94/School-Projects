@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
<section class="wrap auth">
    <div class="card auth-card">
        @include('partials.rings', ['size' => 70])
        <h1>Créer un compte</h1>
        <form method="POST" action="{{ route('register') }}" class="form">
            @csrf
            <x-field name="name" label="Nom">
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" autofocus autocomplete="name">
            </x-field>
            <x-field name="email" label="Adresse e-mail">
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            </x-field>
            <x-field name="password" label="Mot de passe" hint="(8 caractères minimum)">
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
            </x-field>
            <x-field name="password_confirmation" label="Confirmation">
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </x-field>
            <button class="btn btn-primary btn-block">S'inscrire</button>
        </form>
        <p class="muted small">Déjà inscrit ? <a href="{{ route('login', request()->only('epreuve')) }}">Connectez-vous</a></p>
    </div>
</section>
@endsection
