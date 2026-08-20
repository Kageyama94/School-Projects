@extends('modele')

@section('content')

<h1>Connexion</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="form-card">
    <form action="{{ route('login') }}" method="post">
        @csrf
        <div class="form-row">
            <label>Identifiant</label>
            <input type="text" name="name">
        </div>
        <div class="form-row">
            <label>Mot de passe</label>
            <input type="password" name="password" minlength="4">
        </div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px">
            <input type="checkbox" name="remember" id="remember" style="width:auto; margin:0">
            <label for="remember" style="margin:0; font-weight:normal; cursor:pointer">Se souvenir de moi</label>
        </div>
        <input type="submit" value="Se connecter">
    </form>

    <br>
    <p style="font-size:.9rem">Pas encore de compte ? <a href="{{ route('register') }}">S'inscrire</a></p>
    <p style="font-size:.9rem; margin-top:4px"><a href="{{ url('/pizzeria') }}">← Retour à l'accueil</a></p>
</div>

@endsection
