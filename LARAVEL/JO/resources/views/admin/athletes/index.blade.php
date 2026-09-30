@extends('layouts.admin')

@section('title', 'Athlètes')
@section('heading', 'Athlètes')
@section('subheading', $athletes->total().' athlètes')

@section('actions')
    <a href="{{ route('admin.athletes.create') }}" class="btn btn-accent">+ Nouvel athlète</a>
@endsection

@section('body')
<form method="GET" class="filters card">
    <label>
        Recherche
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom ou prénom">
    </label>
    <label>
        Sport
        <select name="sport">
            <option value="">Tous</option>
            @foreach ($sports as $sport)
                <option value="{{ $sport->id }}" @selected(($filters['sport'] ?? null) == $sport->id)>{{ $sport->icon }} {{ $sport->name }}</option>
            @endforeach
        </select>
    </label>
    <label>
        Pays
        <select name="country">
            <option value="">Tous</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(($filters['country'] ?? null) == $country->id)>{{ $country->name }}</option>
            @endforeach
        </select>
    </label>
    <div class="filters-actions">
        <button class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.athletes.index') }}" class="btn btn-ghost-dark">Réinitialiser</a>
    </div>
</form>

<div class="card table-card list-gap">
    <table class="table">
        <thead>
            <tr><th>Athlète</th><th>Sport</th><th class="center">Genre</th><th class="center">Médailles</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($athletes as $athlete)
                <tr>
                    <td>{{ $athlete->country->flag('flag-sm') }} {{ $athlete->fullName() }} <span class="muted small">{{ $athlete->country->code }}</span></td>
                    <td class="muted">{{ $athlete->sport->icon }} {{ $athlete->sport->name }}</td>
                    <td class="center">{{ $athlete->gender->value }}</td>
                    <td class="center">{{ $athlete->results_count ?: '—' }}</td>
                    <td class="right nowrap actions">
                        <a href="{{ route('admin.athletes.edit', $athlete) }}" class="btn btn-sm btn-ghost-dark">Modifier</a>
                        @include('partials.delete-button', ['action' => route('admin.athletes.destroy', $athlete), 'confirm' => 'Supprimer cet athlète ?'])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Aucun athlète ne correspond.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $athletes->links('partials.pagination') }}
@endsection
