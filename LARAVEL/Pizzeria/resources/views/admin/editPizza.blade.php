@extends('modele')

@section('content')

<h1>Modifier la pizza</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $erreur)
            <li>{{ $erreur }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('update', $pizza->id) }}" method="post">
    @csrf
    @method('PUT')

    Nom de la pizza :
    <input type="text" name="name" value="{{ old('name', $pizza->name) }}"><br>

    Prix :
    <input type="number" name="price" step="0.01" min="0" value="{{ old('price', number_format($pizza->price, 2)) }}"><br>

    Description :
    <input type="text" name="description" value="{{ old('description', $pizza->description) }}"><br>

    <input type="submit" value="Enregistrer">
</form>

<a href="{{ route('list') }}"><button type="button">Annuler</button></a>

@endsection
