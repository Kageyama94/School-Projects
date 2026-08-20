@extends('modele')

@section('content')

<h1>Ajouter un livreur</h1>

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
    <form action="{{ route('admin.driver.store') }}" method="post">
        @csrf
        <div class="form-row">
            <label>Nom du livreur</label>
            <input type="text" name="name" value="{{ old('name') }}">
        </div>
        <h2 style="margin:16px 0 12px">Compte de connexion</h2>
        <div class="form-row">
            <label>Identifiant</label>
            <input type="text" name="username" value="{{ old('username') }}">
        </div>
        <div class="form-row">
            <label>Mot de passe</label>
            <input type="password" name="password" minlength="4">
        </div>
        <div class="form-row">
            <label>Confirmer</label>
            <input type="password" name="password_confirmation" minlength="4">
        </div>
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Créer">
            <a href="{{ route('admin.driver.index') }}">Annuler</a>
        </div>
    </form>
</div>

@endsection
