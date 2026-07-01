@extends('modele')

@section('content')

<h1>Commandes</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<h2>Commandes en cours</h2>

@if($orders->isEmpty())
    <p>Aucune commande en cours.</p>
@else
    <table border="1" cellpadding="5">
        <tr>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Pizza</th>
            <th>Prix unit.</th>
            <th>Quantité</th>
            <th>Total</th>
            <th>Adresse</th>
            <th>Livreur</th>
            <th>Statut</th>
            <th>Date</th>
        </tr>
        @foreach($orders as $order)
            @php $customer = $order->customers->first() @endphp
            <tr>
                <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
                <td>{{ $customer?->phone ?? '—' }}</td>
                <td>{{ $order->pizza_name }}</td>
                <td>{{ number_format($order->unit_price, 2) }} €</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ number_format($order->unit_price * $order->quantity, 2) }} €</td>
                <td>{{ $order->address }}, {{ $order->postal_code }}</td>
                <td>
                    <form action="{{ route('order.assign', $order->id) }}" method="post" style="display:flex;gap:4px">
                        @csrf
                        @method('PATCH')
                        <select name="driver_id">
                            <option value="">— Aucun —</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    {{ $order->driver_id == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit">OK</button>
                    </form>
                </td>
                <td>
                    @match($order->status)
                        'preparing'  => 'En préparation',
                        'delivering' => 'En livraison',
                        default      => $order->status,
                    @endmatch
                </td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endforeach
    </table>
@endif

<hr>

<h2>Commandes livrées</h2>

@if($deliveredOrders->isEmpty())
    <p>Aucune commande livrée.</p>
@else
    <table border="1" cellpadding="5">
        <tr>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Pizza</th>
            <th>Prix unit.</th>
            <th>Quantité</th>
            <th>Total</th>
            <th>Adresse</th>
            <th>Livreur</th>
            <th>Date</th>
        </tr>
        @foreach($deliveredOrders as $order)
            @php $customer = $order->customers->first() @endphp
            <tr>
                <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
                <td>{{ $customer?->phone ?? '—' }}</td>
                <td>{{ $order->pizza_name }}</td>
                <td>{{ number_format($order->unit_price, 2) }} €</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ number_format($order->unit_price * $order->quantity, 2) }} €</td>
                <td>{{ $order->address }}, {{ $order->postal_code }}</td>
                <td>{{ $order->driver?->name ?? '—' }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endforeach
    </table>
    {{ $deliveredOrders->links() }}
@endif

<br>
<a href="{{ route('admin') }}">
    <button type="button">Retour au tableau de bord</button>
</a>

@endsection
