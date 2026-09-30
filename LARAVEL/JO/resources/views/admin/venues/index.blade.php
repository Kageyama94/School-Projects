@extends('layouts.admin')

@section('title', 'Sites')
@section('heading', 'Sites de compétition')
@section('subheading', $venues->count().' sites')

@section('actions')
    <a href="{{ route('admin.venues.create') }}" class="btn btn-accent">+ Nouveau site</a>
@endsection

@section('body')
<div class="card table-card">
    <table class="table">
        <thead>
            <tr><th>Site</th><th>Ville</th><th class="center">Capacité</th><th class="center">Épreuves</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($venues as $venue)
                <tr>
                    <td>{{ $venue->name }}</td>
                    <td class="muted">{{ $venue->city }}</td>
                    <td class="center">@number($venue->capacity)</td>
                    <td class="center">{{ $venue->events_count }}</td>
                    <td class="right nowrap actions">
                        <a href="{{ route('admin.venues.edit', $venue) }}" class="btn btn-sm btn-ghost-dark">Modifier</a>
                        @include('partials.delete-button', ['action' => route('admin.venues.destroy', $venue), 'confirm' => 'Supprimer ce site ?'])
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
