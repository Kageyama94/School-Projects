@extends('layouts.admin')

@section('title', 'Pays')
@section('heading', 'Pays participants')
@section('subheading', $countries->count().' délégations')

@section('actions')
    <a href="{{ route('admin.countries.create') }}" class="btn btn-accent">+ Nouveau pays</a>
@endsection

@section('body')
<div class="card table-card">
    <table class="table">
        <thead>
            <tr><th>Pays</th><th>Code CIO</th><th class="center">Athlètes</th><th class="center">Médailles</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($countries as $country)
                <tr>
                    <td><a href="{{ route('countries.show', $country) }}" class="nation">{{ $country->flag() }} {{ $country->name }}</a></td>
                    <td class="muted">{{ $country->code }}</td>
                    <td class="center">{{ $country->athletes_count }}</td>
                    <td class="center">{{ $country->results_count }}</td>
                    <td class="right nowrap actions">
                        <a href="{{ route('admin.countries.edit', $country) }}" class="btn btn-sm btn-ghost-dark">Modifier</a>
                        @include('partials.delete-button', ['action' => route('admin.countries.destroy', $country), 'confirm' => 'Supprimer ce pays ?'])
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
