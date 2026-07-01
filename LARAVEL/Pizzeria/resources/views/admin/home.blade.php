@extends('modele')

@section('content')

<h1>Tableau de bord</h1>

<form action="{{ route('logout') }}" method="post" style="display:inline">
    @csrf
    <input type="submit" value="Se déconnecter">
</form>

<hr>

<h2>Statistiques</h2>
<table border="1" cellpadding="5">
    <tr>
        <td>Pizzas au menu</td>
        <td><strong>{{ $pizzaCount }}</strong></td>
    </tr>
    <tr>
        <td>Commandes totales</td>
        <td><strong>{{ $orderCount }}</strong></td>
    </tr>
    <tr>
        <td>Commandes en cours</td>
        <td><strong>{{ $pendingCount }}</strong></td>
    </tr>
    <tr>
        <td>Livreurs</td>
        <td><strong>{{ $driverCount }}</strong></td>
    </tr>
</table>

<h2>Gestion</h2>
<ul>
    <li><a href="{{ route('list') }}">Pizzas</a> — ajouter/modifier une pizza</li>
    <li><a href="{{ route('order') }}">Commandes</a> — consulter les commandes</li>
    <li><a href="{{ route('delivery') }}">Livreurs</a> — gérer les livreurs</li>
</ul>

@endsection
