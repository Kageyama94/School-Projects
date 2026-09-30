@extends('layouts.app')

@section('content')

<h1>Ajouter une pizza</h1>

<div class="form-card">
    <form action="{{ route('admin.pizza.store') }}" method="post">
        @csrf
        <x-field label="Nom de la pizza">
            <input type="text" name="name" value="{{ old('name') }}">
        </x-field>
        <x-field label="Prix (€)">
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}">
        </x-field>
        <x-field label="Description">
            <input type="text" name="description" value="{{ old('description') }}">
        </x-field>
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Ajouter">
            <a href="{{ route('admin.pizza.index') }}">Annuler</a>
        </div>
    </form>
</div>

@endsection
