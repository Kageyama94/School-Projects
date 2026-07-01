@extends('modele')

@section('content')

<h1>Espace livreur</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<form action="{{ route('logout') }}" method="post" style="display:inline">
    @csrf
    <input type="submit" value="Se déconnecter">
</form>

<hr>

<h2>Commandes à livrer</h2>

@if($orders->isEmpty())
    <p>Aucune commande en attente de livraison.</p>
@else
    <table border="1" cellpadding="5">
        <tr>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Pizza</th>
            <th>Quantité</th>
            <th>Adresse de livraison</th>
            <th>Date commande</th>
            <th>Action</th>
        </tr>
        @foreach($orders as $order)
            @php $customer = $order->customers->first() @endphp
            <tr>
                <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
                <td>{{ $customer?->phone ?? '—' }}</td>
                <td>{{ $order->pizza_name }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->address }}, {{ $order->postal_code }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <form action="{{ route('driver.deliver', $order->id) }}" method="post"
                          onsubmit="return confirm('Confirmer la livraison ?')">
                        @csrf
                        <input type="submit" value="Livrée ✓">
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
@endif

<hr>

<h2>Livraisons effectuées ({{ $history->count() }})</h2>

@if($history->isEmpty())
    <p>Aucune livraison effectuée.</p>
@else
    <table border="1" cellpadding="5">
        <tr>
            <th>Client</th>
            <th>Pizza</th>
            <th>Quantité</th>
            <th>Adresse</th>
            <th>Date commande</th>
        </tr>
        @foreach($history as $order)
            @php $customer = $order->customers->first() @endphp
            <tr>
                <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
                <td>{{ $order->pizza_name }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->address }}, {{ $order->postal_code }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endforeach
    </table>
@endif

@endsection
