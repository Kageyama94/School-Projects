@extends('layouts.app')

@section('content')

<h1>Créer un compte</h1>

<div class="form-card">
    <form action="{{ route('register') }}" method="post">
        @csrf
        <x-field label="Identifiant">
            <input type="text" name="name" value="{{ old('name') }}">
        </x-field>
        <x-field label="Mot de passe">
            <input type="password" name="password" minlength="4">
        </x-field>
        <x-field label="Confirmer le mot de passe">
            <input type="password" name="password_confirmation" minlength="4">
        </x-field>
        <input type="submit" value="S'inscrire">
    </form>

    <br>
    <p style="font-size:.9rem">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    <p style="font-size:.9rem; margin-top:4px"><a href="{{ url('/pizzeria') }}">← Retour à l'accueil</a></p>
</div>

@endsection
