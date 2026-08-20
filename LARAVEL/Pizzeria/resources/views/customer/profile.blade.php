@extends('modele')

@section('content')

<h1>Mon profil</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

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
    <form action="{{ route('customer.profile.update', Auth::id()) }}" method="post">
        @csrf
        @method('PUT')
        <div class="form-row">
            <label>Prénom</label>
            <input type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement">
        </div>
        <div class="form-row">
            <label>Nom</label>
            <input type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement">
        </div>
        <div class="form-row">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $customer?->email) }}">
        </div>
        <div class="form-row">
            <label>Téléphone</label>
            <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}" pattern="[0-9\s\+\-\(\)]{6,20}" inputmode="tel" title="Numéro valide" oninput="this.value=this.value.replace(/[^0-9\s\+\-\(\)]/g,'')">
        </div>
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Enregistrer">
            <a href="{{ route('customer.home', Auth::id()) }}">← Retour</a>
        </div>
    </form>
</div>

@endsection
