@extends('modele')

@section('content')

<h1>Ajouter une pizza</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $erreur)
            <li>{{ $erreur }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('addpizza') }}" method="post">
    @csrf
    Nom de la pizza : <input type="text" name="name" value="{{ old('name') }}"><br><br>
    Prix : <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}"><br><br>
    Description : <input type="text" name="description" value="{{ old('description') }}"><br><br>
    <input type="submit" value="Ajouter">
</form>

<br>
<a href="{{ route('list') }}">
    <button type="button">Retour</button>
</a>

@endsection
