@extends('layouts.admin')

@section('title', 'Épreuves')
@section('heading', 'Gestion des épreuves')
@section('subheading', $events->count().' épreuves · '.\App\Support\Format::number($events->sum('tickets_sum_quantity')).' billets vendus')

@section('actions')
    <a href="{{ route('admin.events.create') }}" class="btn btn-accent">+ Nouvelle épreuve</a>
@endsection

@section('body')
<div class="card table-card">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Épreuve</th>
                <th>Site</th>
                <th class="center">Billets</th>
                <th class="center">Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                <tr @class(['row-cancelled' => $event->isCancelled()])>
                    <td class="nowrap">{{ $event->starts_at->translatedFormat('d/m H\hi') }}</td>
                    <td>
                        <a href="{{ route('events.show', $event) }}">{{ $event->sport->icon }} {{ $event->title() }}</a>
                        <span class="tag tag-{{ $event->gender->value }}">{{ $event->genderLabel() }}</span>
                        @if ($event->team)<span class="tag tag-team">Équipes</span>@endif
                    </td>
                    <td class="muted small">{{ $event->venue->name }}</td>
                    <td class="center nowrap">@number((int) $event->tickets_sum_quantity) / @number($event->capacity)</td>
                    <td class="center">
                        @if ($event->isCancelled())
                            <span class="status status-cancelled">Annulée</span>
                        @elseif ($event->results_count)
                            <span class="status status-done">🏅 {{ $event->results_count }}</span>
                        @elseif ($event->isPast())
                            <span class="status status-todo">Résultats à saisir</span>
                        @else
                            <span class="muted small">À venir</span>
                        @endif
                    </td>
                    <td class="right nowrap actions">
                        @if ($event->isPast() && ! $event->isCancelled())
                            <a href="{{ route('admin.events.results', $event) }}" class="btn btn-sm btn-ghost-dark" title="Saisir les résultats" aria-label="Saisir les résultats">🏅</a>
                        @endif
                        <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-ghost-dark">Modifier</a>
                        @if (! $event->isPast() && ! $event->isCancelled())
                            @php $sold = (int) $event->tickets_sum_quantity; @endphp
                            <form method="POST" action="{{ route('admin.events.cancel', $event) }}"
                                  onsubmit="return confirm(@js('Annuler « '.$event->name.' » ?'.($sold ? " Les $sold billet(s) vendu(s) seront remboursés." : '').' Cette action est définitive.'))">
                                @csrf
                                <button class="btn btn-sm btn-warning">Annuler</button>
                            </form>
                        @endif
                        {{-- Avec des billets (même annulés), l'épreuve fait partie de l'historique : pas de suppression. --}}
                        @if ($event->tickets_count === 0)
                            @include('partials.delete-button', ['action' => route('admin.events.destroy', $event), 'confirm' => 'Supprimer cette épreuve ?'])
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
