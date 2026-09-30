@extends('layouts.app')

@section('content')

<h1>Modifier le livreur</h1>

<div class="form-card">
    <form action="{{ route('admin.driver.update', $driver->id) }}" method="post">
        @csrf
        @method('PUT')
        <x-field label="Nom du livreur">
            <input type="text" name="name" value="{{ old('name', $driver->name) }}">
        </x-field>
        @if($driver->user)
            <p style="margin:12px 0; font-size:.9rem; color:#555">Compte lié : <strong>{{ $driver->user->name }}</strong></p>
        @endif
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Enregistrer">
            <a href="{{ route('admin.driver.index') }}">Annuler</a>
        </div>
    </form>
</div>

@endsection
