@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
<section class="wrap auth">
    <div class="card auth-card">
        @include('partials.rings', ['size' => 70])
        <h1>Mot de passe oublié</h1>
        <p class="muted small">Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
        <form method="POST" action="{{ route('password.email') }}" class="form">
            @csrf
            <x-field name="email" label="Adresse e-mail">
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </x-field>
            <button class="btn btn-primary btn-block">Envoyer le lien</button>
        </form>
        <p class="muted small"><a href="{{ route('login') }}">← Retour à la connexion</a></p>
    </div>
</section>
@endsection
