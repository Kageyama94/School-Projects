@extends('modele')

@section('content')

<h1>Détail de la commande</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

@php
    $first    = $orders->first();
    $customer = $first->customers->first();
    $total    = $orders->sum('line_total');
@endphp

<div class="form-card" style="max-width:600px">

    <table style="box-shadow:none; margin:0 0 24px 0">
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

    <table style="box-shadow:none; margin:0 0 24px 0">
        <tr>
            <th style="background:none; color:#555; width:40%">Client</th>
            <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Téléphone</th>
            <td>{{ $customer?->phone ?? '—' }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Adresse</th>
            <td>{{ $first->address }}, {{ $first->postal_code }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Date commande</th>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th style="background:none; color:#555">Date livraison</th>
            <td>{{ $first->delivered_at ? $first->delivered_at->format('d/m/Y H:i') : '—' }}</td>
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

    <a href="{{ route('admin.order.index') }}">← Retour aux commandes</a>
</div>

@endsection
