@extends('modele')

@section('content')

<h1>Détail de la commande</h1>

@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

@php
    $first = $orders->first();
    $total = $orders->sum('line_total');
@endphp

<div class="form-card" style="max-width:560px">

    <table style="box-shadow:none; margin:0 0 20px 0">
        <tr>
            <th>Pizza</th>
            <th>Prix unit.</th>
            <th>Qté</th>
            <th>Sous-total</th>
        </tr>
        @foreach($orders as $order)
        <tr>
            <td>{{ $order->pizza_name }}</td>
            <td>{{ number_format($order->unit_price, 2, ',', ' ') }} €</td>
            <td>{{ $order->quantity }}</td>
            <td>{{ number_format($order->line_total, 2, ',', ' ') }} €</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="3" style="text-align:right; font-weight:600">Total</td>
            <td><strong>{{ number_format($total, 2, ',', ' ') }} €</strong></td>
        </tr>
    </table>

    <table style="box-shadow:none; margin:0">
        <tr>
            <th style="background:none; color:#555; width:40%">Adresse</th>
            <td>{{ $first->address }}, {{ $first->postal_code }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Date</th>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Livreur</th>
            <td>{{ $first->driver_name ?? '—' }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Statut</th>
            <td>
                @include('partials.status-badge', ['status' => $first->status])
            </td>
        </tr>
    </table>

    <div style="margin-top:20px; display:flex; gap:12px; align-items:center">
        <a href="{{ route('customer.home', Auth::id()) }}">← Retour à mes commandes</a>
        @if($first->status === 'pending')
            <form action="{{ route('order.cancel', [Auth::id(), $orders->first()->order_group_id]) }}" method="post"
                  onsubmit="return confirm('Annuler cette commande ?')">
                @csrf
                @method('DELETE')
                <input type="submit" value="Annuler la commande" style="background:#7f8c8d">
            </form>
        @endif
    </div>
</div>

@endsection
