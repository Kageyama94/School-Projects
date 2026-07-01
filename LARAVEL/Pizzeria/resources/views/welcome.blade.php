@extends('modele')

@section('content')

<h1>Pizza</h1>

<h2>Nos pizzas</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Nom</th>
        <th>Prix</th>
        <th>Description</th>
    </tr>
    @foreach($pizzas as $pizza)
    <tr>
        <td>{{ $pizza->name }}</td>
        <td>{{ number_format($pizza->price, 2) }} €</td>
        <td>{{ $pizza->description }}</td>
    </tr>
    @endforeach
</table>

<br>

<a href="{{ route('login') }}">Se connecter</a>
<a href="{{ route('register') }}">S'inscrire</a>

@endsection