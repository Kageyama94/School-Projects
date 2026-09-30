@extends('layouts.admin')

@section('title', 'Résultats')
@section('heading', 'Résultats : '.$event->fullTitle())
@section('subheading', ($event->team ? 'Épreuve par équipes : choisissez les pays médaillés.' : 'Choisissez les athlètes médaillés.').($event->sport->two_bronzes ? ' Deux médailles de bronze sont décernées dans ce sport.' : ''))

@section('body')
<form method="POST" action="{{ route('admin.events.results.update', $event) }}" class="card form">
    @csrf
    @method('PUT')

    @foreach ($slots as $slot => $medal)
        <label class="medal-field">
            <span>
                @include('partials.medal', ['medal' => $medal])
                {{ $medal->fullLabel() }}@if ($event->sport->two_bronzes && $medal === \App\Enums\Medal::Bronze) ({{ $slot === 'bronze_2' ? 2 : 1 }})@endif
            </span>
            <select name="{{ $slot }}">
                <option value="">— Non attribuée —</option>
                @foreach ($candidates as $candidate)
                    <option value="{{ $candidate->id }}" @selected(old($slot, $current[$slot]) == $candidate->id)>
                        @if ($event->team)
                            {{ $candidate->name }} ({{ $candidate->code }})
                        @else
                            {{ $candidate->fullName() }} ({{ $candidate->country->code }})
                        @endif
                    </option>
                @endforeach
            </select>
            <x-error :field="$slot" />
        </label>
    @endforeach

    <p class="muted small">Les médailles s'attribuent dans l'ordre (or, puis argent, puis bronze). Tout laisser vide efface les résultats.</p>

    <div class="form-actions">
        <a href="{{ route('admin.events.index') }}" class="btn btn-ghost-dark">Annuler</a>
        <button class="btn btn-primary">Enregistrer les résultats</button>
    </div>
</form>
@endsection
