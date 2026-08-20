@extends('modele')

@section('content')

<h1>Bienvenue, {{ $customer?->first_name ?? Auth::user()->name }} !</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="nav-actions">
    <a href="{{ route('order.create', Auth::id()) }}"><button type="button">🍕 Passer une commande</button></a>
    <a href="{{ route('customer.profile.edit', Auth::id()) }}"><button type="button">Mon profil</button></a>
    <form action="{{ route('logout') }}" method="post">
        @csrf
        <input type="submit" value="Se déconnecter">
    </form>
</div>

<h2>Historique de mes commandes</h2>

@if($groups->isEmpty())
    <p style="color:#777">Vous n'avez pas encore passé de commande.</p>
@else
    <table>
        <tr>
            <th>Date</th>
            <th>Pizzas</th>
            <th>Total</th>
            <th>Adresse</th>
            <th>Statut</th>
            <th></th>
        </tr>
        @foreach($groups as $groupId => $items)
        @php
            $first  = $items->first();
            $total  = $items->sum('line_total');
            $pizzas = $items->map(fn($o) => $o->pizza_name . ' x' . $o->quantity)->join(', ');
        @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ route('order.show', [Auth::id(), $groupId]) }}'">
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $pizzas }}</td>
            <td>{{ number_format($total, 2, ',', ' ') }} €</td>
            <td>{{ $first->address }}, {{ $first->postal_code }}</td>
            <td>
                @include('partials.status-badge', ['status' => $first->status])
            </td>
            <td onclick="event.stopPropagation()">
                @if($first->status === 'pending')
                    <form action="{{ route('order.cancel', [Auth::id(), $groupId]) }}" method="post"
                          onsubmit="return confirm('Annuler cette commande ?')">
                        @csrf
                        @method('DELETE')
                        <input type="submit" value="Annuler" style="background:#7f8c8d">
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
    {{ $groupsPage->links() }}
@endif

@endsection
