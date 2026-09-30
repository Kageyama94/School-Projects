@extends('layouts.app')

@section('content')

<h1>Détail de la commande</h1>

@php
    $first    = $orders->first();
    $customer = $first->customers->first();
    $total    = $orders->sum('line_total');
@endphp

<div class="form-card" style="max-width:600px">

    @include('partials.order-lines', ['orders' => $orders, 'total' => $total])

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
