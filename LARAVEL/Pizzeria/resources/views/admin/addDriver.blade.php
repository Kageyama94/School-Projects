@extends('modele')

@section('content')

<h1>Ajouter un livreur</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $erreur)
            <li>{{ $erreur }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('driver.create') }}" method="post">
    @csrf
    Nom du livreur : <input type="text" name="name" value="{{ old('name') }}"><br><br>
    <strong>Compte de connexion</strong><br><br>
    Identifiant : <input type="text" name="username" value="{{ old('username') }}"><br><br>
    Mot de passe : <input type="password" name="password" minlength="4"><br><br>
    Confirmer : <input type="password" name="password_confirmation" minlength="4"><br><br>
    <input type="submit" value="Créer">
</form>

<br>
<a href="{{ route('delivery') }}">
    <button type="button">Retour</button>
</a>

@endsection
