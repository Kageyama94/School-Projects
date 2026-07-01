@extends('modele')

@section('content')

<h1>Connexion</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if ($errors->any())
    <p style="color:red">{{ $errors->first() }}</p>
@endif

<form action="{{ route('login') }}" method="post">
    @csrf
    Identifiant :
    <input type="text" name="name"><br>
    Mot de passe :
    <input type="password" name="password"><br>
    <label><input type="checkbox" name="remember"> Se souvenir de moi</label><br>
    <input type="submit" value="Se connecter">
</form>

<br>
<p>Pas encore de compte ? <a href="{{ route('register') }}">S'inscrire</a></p>

@endsection