@extends('modele')

@section('content')

<h1>Ajouter une pizza</h1>

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
    <form action="{{ route('admin.pizza.store') }}" method="post">
        @csrf
        <div class="form-row">
            <label>Nom de la pizza</label>
            <input type="text" name="name" value="{{ old('name') }}">
        </div>
        <div class="form-row">
            <label>Prix (€)</label>
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}">
        </div>
        <div class="form-row">
            <label>Description</label>
            <input type="text" name="description" value="{{ old('description') }}">
        </div>
        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
            <input type="submit" value="Ajouter">
            <a href="{{ route('admin.pizza.index') }}">Annuler</a>
        </div>
    </form>
</div>

@endsection
