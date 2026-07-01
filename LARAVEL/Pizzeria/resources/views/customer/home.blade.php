@extends('modele')

@section('content')

<h1>Bienvenue, {{ Auth::user()->name }}</h1>

<a href="{{ route('order.form', Auth::id()) }}">Passer une commande</a>

<form action="{{ route('logout') }}" method="post" style="display:inline; margin-left:10px">
    @csrf
    <input type="submit" value="Se déconnecter">
</form>

<hr>

<h2>Historique de mes commandes</h2>

@if($orders->isEmpty())
    <p>Vous n'avez pas encore passé de commande.</p>
@else
    <table border="1" cellpadding="5">
        <tr>
            <th>Pizza</th>
            <th>Prix unit.</th>
            <th>Quantité</th>
            <th>Total</th>
            <th>Adresse</th>
            <th>Date</th>
            <th>Statut</th>
        </tr>
        @foreach($orders as $order)
        <tr>
            <td>{{ $order->pizza_name }}</td>
            <td>{{ number_format($order->unit_price, 2) }} €</td>
            <td>{{ $order->quantity }}</td>
            <td>{{ number_format($order->unit_price * $order->quantity, 2) }} €</td>
            <td>{{ $order->address }}, {{ $order->postal_code }}</td>
            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
            <td>
                @match($order->status)
                    'preparing'  => 'En préparation',
                    'delivering' => 'En livraison',
                    'delivered'  => '✓ Livrée',
                    default      => $order->status,
                @endmatch
            </td>
        </tr>
        @endforeach
    </table>
@endif

@endsection
