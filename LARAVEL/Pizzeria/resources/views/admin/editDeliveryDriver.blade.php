@extends('modele')

@section('content')

<h1>Modifier le livreur</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $erreur)
            <li>{{ $erreur }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('driver.update', $driver->id) }}" method="post">
    @csrf
    @method('PUT')
    Nom du livreur : <input type="text" name="name" value="{{ old('name', $driver->name) }}"><br><br>
    @if($driver->user)
        Compte lié : <strong>{{ $driver->user->name }}</strong><br><br>
    @endif
    <input type="submit" value="Enregistrer">
</form>

<br>
<a href="{{ route('delivery') }}">
    <button type="button">Retour</button>
</a>

@endsection
