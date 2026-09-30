@extends('layouts.admin')

@section('title', 'Sports')
@section('heading', 'Sports')
@section('subheading', $sports->count().' sports au programme')

@section('actions')
    <a href="{{ route('admin.sports.create') }}" class="btn btn-accent">+ Nouveau sport</a>
@endsection

@section('body')
<div class="card table-card">
    <table class="table">
        <thead>
            <tr><th>Sport</th><th class="center">Épreuves</th><th class="center">Athlètes</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($sports as $sport)
                <tr>
                    <td><a href="{{ route('sports.show', $sport) }}">{{ $sport->icon }} {{ $sport->name }}</a></td>
                    <td class="center">{{ $sport->events_count }}</td>
                    <td class="center">{{ $sport->athletes_count }}</td>
                    <td class="right nowrap actions">
                        <a href="{{ route('admin.sports.edit', $sport) }}" class="btn btn-sm btn-ghost-dark">Modifier</a>
                        @include('partials.delete-button', ['action' => route('admin.sports.destroy', $sport), 'confirm' => 'Supprimer ce sport ?'])
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
