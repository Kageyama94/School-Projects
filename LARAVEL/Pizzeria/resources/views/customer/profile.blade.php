@extends('layouts.app')

@section('content')

<h1>Mon profil</h1>

<div class="form-card">
    <form action="{{ route('customer.profile.update', Auth::id()) }}" method="post">
        @csrf
        @method('PUT')
        <x-field label="Prénom">
            <input type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement">
        </x-field>
        <x-field label="Nom">
            <input type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement">
        </x-field>
        <x-field label="Email">
            <input type="email" name="email" value="{{ old('email', $customer?->email) }}">
        </x-field>
        <x-field label="Téléphone">
            <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}" pattern="[0-9\s\+\-\(\)]{6,20}" inputmode="tel" title="Numéro valide" oninput="this.value=this.value.replace(/[^0-9\s\+\-\(\)]/g,'')">
        </x-field>
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Enregistrer">
            <a href="{{ route('customer.home', Auth::id()) }}">← Retour</a>
        </div>
    </form>
</div>

@endsection
