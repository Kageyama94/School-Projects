@extends('modele')

@section('content')

<h1>Livreurs</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<table border="1" cellpadding="5">
    <tr>
        <th>Nom</th>
        <th>Compte lié</th>
        <th>Actions
            <a href="{{ route('driver.add') }}">
                <button type="button">Ajouter</button>
            </a>
        </th>
    </tr>
    @forelse($drivers as $driver)
    <tr>
        <td>{{ $driver->name }}</td>
        <td>{{ $driver->user?->name ?? '—' }}</td>
        <td>
            <a href="{{ route('driver.edit', $driver->id) }}">
                <button type="button">Modifier</button>
            </a>

            <form action="{{ route('driver.destroy', $driver->id) }}" method="post" style="display:inline"
                  onsubmit="return confirm('Supprimer {{ addslashes($driver->name) }} ?')">
                @csrf
                @method('DELETE')
                <input type="submit" value="Supprimer">
            </form>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="3">Aucun livreur enregistré.</td>
    </tr>
    @endforelse
</table>

<br>
<a href="{{ route('admin') }}">
    <button type="button">Retour au tableau de bord</button>
</a>

@endsection
