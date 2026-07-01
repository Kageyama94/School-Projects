@extends('modele')

@section('content')

<h1>Commande confirmée</h1>

<p>Merci de votre commande, {{ Auth::user()->name }} !</p>
<p>Adresse de livraison : <strong>{{ session('orderAddress') }}</strong></p>

<h2>Récapitulatif</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Pizza</th>
        <th>Prix unitaire</th>
        <th>Quantité</th>
        <th>Sous-total</th>
    </tr>
    @foreach(session('summary', []) as $line)
    <tr>
        <td>{{ $line['name'] }}</td>
        <td>{{ number_format($line['unit_price'], 2) }} €</td>
        <td>{{ $line['quantity'] }}</td>
        <td>{{ number_format($line['subtotal'], 2) }} €</td>
    </tr>
    @endforeach
    <tr>
        <td colspan="3"><strong>Total</strong></td>
        <td><strong>{{ number_format(session('grandTotal', 0), 2) }} €</strong></td>
    </tr>
</table>

<br>
<a href="{{ route('customer.home', Auth::id()) }}">Retour à l'accueil</a>

@endsection
