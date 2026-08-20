@extends('modele')

@section('content')

<h1>Espace livreur</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<div class="nav-actions">
    <form action="{{ route('logout') }}" method="post">
        @csrf
        <input type="submit" value="Se déconnecter">
    </form>
</div>

<h2>Commandes à livrer</h2>

@if($activeGroups->isEmpty())
    <p style="color:#777">Aucune commande en attente de livraison.</p>
@else
    <table>
        <tr>
            <th>Date commande</th>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Pizzas</th>
            <th>Total</th>
            <th>Adresse</th>
            <th>Action</th>
        </tr>
        @foreach($activeGroups as $groupId => $items)
        @php
            $first    = $items->first();
            $customer = $first->customers->first();
            $total    = $items->sum('line_total');
            $pizzas   = $items->map(fn($o) => $o->pizza_name . ' x' . $o->quantity)->join(', ');
        @endphp
        <tr>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
            <td>{{ $customer?->phone ?? '—' }}</td>
            <td>{{ $pizzas }}</td>
            <td>{{ number_format($total, 2, ',', ' ') }} €</td>
            <td>{{ $first->address }}, {{ $first->postal_code }}</td>
            <td>
                <form action="{{ route('driver.deliver', $groupId) }}" method="post"
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

<h2>Livraisons effectuées ({{ $historyPage?->total() ?? 0 }})</h2>

@if($historyGroups->isEmpty())
    <p style="color:#777">Aucune livraison effectuée.</p>
@else
    <table>
        <tr>
            <th>Date commande</th>
            <th>Date livraison</th>
            <th>Client</th>
            <th>Pizzas</th>
            <th>Total</th>
            <th>Adresse</th>
        </tr>
        @foreach($historyGroups as $groupId => $items)
        @php
            $first    = $items->first();
            $customer = $first->customers->first();
            $total    = $items->sum('line_total');
            $pizzas   = $items->map(fn($o) => $o->pizza_name . ' x' . $o->quantity)->join(', ');
        @endphp
        <tr>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $first->delivered_at ? $first->delivered_at->format('d/m/Y H:i') : '—' }}</td>
            <td>{{ $customer ? $customer->first_name.' '.$customer->last_name : '—' }}</td>
            <td>{{ $pizzas }}</td>
            <td>{{ number_format($total, 2, ',', ' ') }} €</td>
            <td>{{ $first->address }}, {{ $first->postal_code }}</td>
        </tr>
        @endforeach
    </table>
    {{ $historyPage->links() }}
@endif

@endsection
