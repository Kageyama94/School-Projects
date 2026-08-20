@extends('modele')

@section('content')

<h1>Livreurs</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<table>
    <tr>
        <th>Nom</th>
        <th>Compte lié</th>
        <th style="white-space:nowrap">
            Actions &nbsp;
            <a href="{{ route('admin.driver.create') }}"><button type="button" style="font-size:.8rem; padding:4px 10px">+ Ajouter</button></a>
        </th>
    </tr>
    @forelse($drivers as $driver)
    <tr>
        <td>{{ $driver->name }}</td>
        <td>{{ $driver->user?->name ?? '—' }}</td>
        <td style="white-space:nowrap">
            <a href="{{ route('admin.driver.edit', $driver->id) }}"><button type="button">Modifier</button></a>
            <form action="{{ route('admin.driver.destroy', $driver->id) }}" method="post" style="display:inline"
                  onsubmit="return confirm('Supprimer ' + @js($driver->name) + ' ?')">
                @csrf
                @method('DELETE')
                <input type="submit" value="Supprimer" style="background:#7f8c8d">
            </form>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="3" style="color:#777">Aucun livreur enregistré.</td>
    </tr>
    @endforelse
</table>

<a href="{{ route('admin.home') }}"><button type="button">← Tableau de bord</button></a>

@endsection
