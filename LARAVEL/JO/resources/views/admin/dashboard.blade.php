@extends('layouts.admin')

@php
    $nearlyFull = \App\Http\Controllers\Admin\DashboardController::NEARLY_FULL;
@endphp

@section('title', 'Tableau de bord')
@section('heading', 'Tableau de bord')
@section('subheading', 'Billetterie et suivi des épreuves')

@section('body')
<div class="dash-top">
    <div class="card hero-figure">
        <div class="kpi-label">Recettes de la billetterie</div>
        <div class="hero-value">@euros($revenue)</div>
        <div class="muted small">Hors épreuves annulées · prix payé au moment de la réservation</div>
    </div>
    <div class="kpi-row">
        <div class="card kpi">
            <div class="kpi-label">Billets vendus</div>
            <div class="kpi-value">@number($sold)</div>
        </div>
        <div class="card kpi">
            <div class="kpi-label">Remplissage des épreuves à venir</div>
            <div class="kpi-value">@percent($fillRate)</div>
        </div>
        <div class="card kpi">
            <div class="kpi-label">Spectateurs inscrits</div>
            <div class="kpi-value">@number($spectators)</div>
        </div>
        <div class="card kpi">
            <div class="kpi-label">Billets remboursés</div>
            <div class="kpi-value">@number($refunded)</div>
        </div>
    </div>
</div>

<div class="grid-2 dash-grid">
    <div class="card table-card">
        <h2 class="card-title">Remplissage des épreuves à venir</h2>
        <table class="table">
            <thead>
                <tr><th>Épreuve</th><th>Date</th><th class="meter-col">Places vendues</th></tr>
            </thead>
            <tbody>
                @forelse ($upcoming as $event)
                    @php $full = $event->fill_rate >= $nearlyFull; @endphp
                    <tr>
                        <td><a href="{{ route('events.show', $event) }}">{{ $event->fullTitle() }}</a></td>
                        <td class="nowrap muted small">{{ $event->starts_at->translatedFormat('j M H\hi') }}</td>
                        <td>
                            <div class="meter-cell" title="@number((int) $event->tickets_sum_quantity) / @number($event->capacity) places">
                                <div @class(['meter', 'meter-warning' => $full]) role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($event->fill_rate * 100) }}" aria-label="Taux de remplissage">
                                    <div style="width: {{ min(100, $event->fill_rate * 100) }}%"></div>
                                </div>
                                <span class="meter-value">@percent($event->fill_rate)</span>
                                @if ($full)
                                    <span class="status status-todo">⚠ Presque complet</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">Aucune épreuve à venir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <aside class="stack-lg">
        <div class="card">
            <h2>Résultats à saisir</h2>
            @forelse ($awaitingResults as $event)
                <a href="{{ route('admin.events.results', $event) }}" class="champion-row">
                    <span class="status status-todo">🏅</span>
                    <div class="grow">
                        <div><strong>{{ $event->title() }}</strong></div>
                        <div class="muted small">{{ $event->genderLabel() }} · {{ $event->starts_at->translatedFormat('j F H\hi') }}</div>
                    </div>
                </a>
            @empty
                <p class="muted">✅ Tous les résultats sont à jour.</p>
            @endforelse
        </div>

        <div class="card">
            <h2>Meilleures ventes</h2>
            <table class="table">
                <tbody>
                    @forelse ($topSales as $event)
                        <tr>
                            <td><a href="{{ route('events.show', $event) }}">{{ $event->sport->icon }} {{ $event->name }}</a> <span class="muted small">{{ $event->sport->name }}</span></td>
                            <td class="right nowrap strong">@euros((int) $event->revenue)</td>
                        </tr>
                    @empty
                        <tr><td class="muted">Aucune vente pour l'instant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </aside>
</div>
@endsection
