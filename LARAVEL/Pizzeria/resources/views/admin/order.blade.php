@extends('modele')

@section('content')

<h1>Commandes</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('admin.order.index') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:20px">
    <select name="driver_id">
        <option value="">Tous les livreurs</option>
        @foreach($drivers as $driver)
            <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                {{ $driver->name }}
            </option>
        @endforeach
    </select>
    <input type="date" name="date" value="{{ request('date') }}">
    <button type="submit">Filtrer</button>
    @if(request('driver_id') || request('date'))
        <a href="{{ route('admin.order.index') }}"><button type="button">✕ Réinitialiser</button></a>
    @endif
</form>

<h2>Nouvelles commandes</h2>

@if($pendingGroups->isEmpty())
    <p style="color:#777">Aucune nouvelle commande.</p>
@else
    <table>
        <tr>
            <th>N°</th>
            <th>Date</th>
            <th>Total</th>
            <th>Action</th>
        </tr>
        @foreach($pendingGroups as $groupId => $items)
        @php
            $first = $items->first();
            $total = $items->sum('line_total');
        @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.order.show', $groupId) }}'">
            <td>#{{ $first->id }}</td>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ number_format($total, 2, ',', ' ') }} €</td>
            <td onclick="event.stopPropagation()">
                <form action="{{ route('admin.order.accept', $groupId) }}" method="post">
                    @csrf
                    @method('PATCH')
                    <button type="submit">Accepter ✓</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
@endif

<hr>

<h2>Commandes en cours</h2>

@if($activeGroups->isEmpty())
    <p style="color:#777">Aucune commande en cours.</p>
@else
    <table>
        <tr>
            <th>N°</th>
            <th>Date</th>
            <th>Total</th>
            <th>Statut</th>
            <th>Assigner un livreur</th>
        </tr>
        @foreach($activeGroups as $groupId => $items)
        @php
            $first = $items->first();
            $total = $items->sum('line_total');
        @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.order.show', $groupId) }}'">
            <td>#{{ $first->id }}</td>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ number_format($total, 2, ',', ' ') }} €</td>
            <td>
                @include('partials.status-badge', ['status' => $first->status])
            </td>
            <td onclick="event.stopPropagation()">
                <form action="{{ route('admin.order.assign', $groupId) }}" method="post" style="display:flex; gap:4px">
                    @csrf
                    @method('PATCH')
                    <select name="driver_id">
                        <option value="">— Aucun —</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ $first->driver_id == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit">OK</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
@endif

<hr>

<h2>Commandes livrées</h2>

@if($deliveredGroups->isEmpty())
    <p style="color:#777">Aucune commande livrée.</p>
@else
    <table>
        <tr>
            <th>N°</th>
            <th>Date commande</th>
            <th>Total</th>
            <th>Livreur</th>
        </tr>
        @foreach($deliveredGroups as $groupId => $items)
        @php
            $first = $items->first();
            $total = $items->sum('line_total');
        @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.order.show', $groupId) }}'">
            <td>#{{ $first->id }}</td>
            <td>{{ $first->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ number_format($total, 2, ',', ' ') }} €</td>
            <td>{{ $first->driver_name ?? '—' }}</td>
        </tr>
        @endforeach
    </table>
    {{ $deliveredPage->links() }}
@endif

<br>
<a href="{{ route('admin.home') }}"><button type="button">← Tableau de bord</button></a>

@endsection
