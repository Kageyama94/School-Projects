@extends('modele')

@section('content')

<h1>Créer un compte</h1>

@if ($errors->any())
    <div class="alert-error">
        <ul style="list-style:none">
            @foreach ($errors->all() as $erreur)
                <li>{{ $erreur }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-card">
    <form action="{{ route('register') }}" method="post">
        @csrf
        <div class="form-row">
            <label>Identifiant</label>
            <input type="text" name="name" value="{{ old('name') }}">
        </div>
        <div class="form-row">
            <label>Mot de passe</label>
            <input type="password" name="password" minlength="4">
        </div>
        <div class="form-row">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" minlength="4">
        </div>
        <input type="submit" value="S'inscrire">
    </form>

    <br>
    <p style="font-size:.9rem">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    <p style="font-size:.9rem; margin-top:4px"><a href="{{ url('/pizzeria') }}">← Retour à l'accueil</a></p>
</div>

@endsection
