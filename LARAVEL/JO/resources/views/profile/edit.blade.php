@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<section class="page-head">
    <div class="wrap">
        <h1>Mon profil</h1>
        <p>Membre depuis le {{ $user->created_at->translatedFormat('j F Y') }}.</p>
    </div>
</section>

<section class="wrap grid-halves">
    <form method="POST" action="{{ route('profile.update') }}" class="card form">
        @csrf
        @method('PUT')
        <h2>Informations</h2>
        <x-field name="name" label="Nom">
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name">
        </x-field>
        <x-field name="email" label="Adresse e-mail">
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
        </x-field>
        <x-field name="email_password" label="Mot de passe actuel" hint="(uniquement pour changer d'adresse e-mail)">
            <input type="password" name="email_password" autocomplete="current-password">
        </x-field>
        <div class="form-actions">
            <button class="btn btn-primary">Enregistrer</button>
        </div>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="card form">
        @csrf
        @method('PUT')
        <h2>Mot de passe</h2>
        <x-field name="current_password" label="Mot de passe actuel">
            <input type="password" name="current_password" required autocomplete="current-password">
        </x-field>
        <x-field name="password" label="Nouveau mot de passe" hint="(8 caractères minimum)">
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </x-field>
        <x-field name="password_confirmation" label="Confirmation">
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </x-field>
        <div class="form-actions">
            <button class="btn btn-primary">Changer le mot de passe</button>
        </div>
    </form>

    <form method="POST" action="{{ route('profile.destroy') }}" class="card form danger-zone span-all"
          onsubmit="return confirm('Supprimer définitivement votre compte ? Vos billets à venir seront annulés.')">
        @csrf
        @method('DELETE')
        <h2>Supprimer mon compte</h2>
        <p class="muted small">
            Votre compte sera supprimé et vos billets à venir annulés et remboursés : leurs places redeviendront disponibles.
            Cette action est définitive.
        </p>
        <x-field name="delete_password" label="Confirmez avec votre mot de passe">
            <input type="password" name="delete_password" required autocomplete="current-password">
        </x-field>
        <div class="form-actions">
            <button class="btn btn-danger">Supprimer mon compte</button>
        </div>
    </form>
</section>
@endsection
