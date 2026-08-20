@extends('modele')

@section('content')

<h1>Bienvenue chez Pizzeria</h1>

<p style="color:#555; margin-bottom:24px">Découvrez nos pizzas et commandez en ligne.</p>

<h2>Nos pizzas</h2>
<table>
    <tr>
        <th>Nom</th>
        <th>Prix</th>
        <th>Description</th>
    </tr>
    @foreach($pizzas as $pizza)
    <tr>
        <td>{{ $pizza->name }}</td>
        <td>{{ number_format($pizza->price, 2, ',', ' ') }} €</td>
        <td>{{ $pizza->description }}</td>
    </tr>
    @endforeach
</table>

<br>
<div style="display:flex; gap:12px">
    <a href="{{ route('login') }}"><button>Se connecter</button></a>
    <a href="{{ route('register') }}"><button type="button">S'inscrire</button></a>
</div>

@endsection
