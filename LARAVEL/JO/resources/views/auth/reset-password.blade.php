@extends('layouts.app')

@section('title', 'Nouveau mot de passe')

@section('content')
<section class="wrap auth">
    <div class="card auth-card">
        @include('partials.rings', ['size' => 70])
        <h1>Nouveau mot de passe</h1>
        <form method="POST" action="{{ route('password.update') }}" class="form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field name="email" label="Adresse e-mail">
                <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
            </x-field>
            <x-field name="password" label="Nouveau mot de passe" hint="(8 caractères minimum)">
                <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password">
            </x-field>
            <x-field name="password_confirmation" label="Confirmation">
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </x-field>
            <button class="btn btn-primary btn-block">Enregistrer</button>
        </form>
    </div>
</section>
@endsection
