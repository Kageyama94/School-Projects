@extends('modele')

@section('content')

<h1>Créer un compte</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $erreur)
            <li>{{ $erreur }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('register') }}" method="post">
    @csrf
    Identifiant :
    <input type="text" name="name" value="{{ old('name') }}"><br>
    Mot de passe :
    <input type="password" name="password" minlength="4"><br>
    Confirmer le mot de passe :
    <input type="password" name="password_confirmation" minlength="4"><br>
    <input type="submit" value="S'inscrire">
</form>

<br>
Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a>

@endsection