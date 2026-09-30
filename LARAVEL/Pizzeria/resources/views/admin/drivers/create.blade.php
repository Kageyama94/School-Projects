@extends('layouts.app')

@section('content')

<h1>Ajouter un livreur</h1>

<div class="form-card">
    <form action="{{ route('admin.driver.store') }}" method="post">
        @csrf
        <x-field label="Nom du livreur">
            <input type="text" name="name" value="{{ old('name') }}">
        </x-field>
        <h2 style="margin:16px 0 12px">Compte de connexion</h2>
        <x-field label="Identifiant">
            <input type="text" name="username" value="{{ old('username') }}">
        </x-field>
        <x-field label="Mot de passe">
            <input type="password" name="password" minlength="4">
        </x-field>
        <x-field label="Confirmer">
            <input type="password" name="password_confirmation" minlength="4">
        </x-field>
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Créer">
            <a href="{{ route('admin.driver.index') }}">Annuler</a>
        </div>
    </form>
</div>

@endsection
