@extends('layouts.app')

@section('title', 'Tableau des médailles')

@section('content')
<section class="page-head">
    <div class="wrap">
        <h1>Tableau des médailles</h1>
        <p>Classement officiel : or, puis argent, puis bronze.</p>
    </div>
</section>

<section class="wrap">
    <div class="card table-card">
        <table class="table medal-table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Nation</th>
                    <th class="center"><span class="dot dot-gold"></span> Or</th>
                    <th class="center"><span class="dot dot-silver"></span> Argent</th>
                    <th class="center"><span class="dot dot-bronze"></span> Bronze</th>
                    <th class="center">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = 0; $prev = null; @endphp
                @forelse ($table as $i => $country)
                    @php
                        // Ex æquo : même rang si or, argent et bronze identiques.
                        $key = "$country->gold-$country->silver-$country->bronze";
                        if ($key !== $prev) { $rank = $i + 1; $prev = $key; }
                    @endphp
                    <tr>
                        <td><span class="rank rank-{{ $rank }}">{{ $rank }}</span></td>
                        <td><a href="{{ route('countries.show', $country) }}" class="nation">{{ $country->flag() }} {{ $country->name }} <span class="muted small">{{ $country->code }}</span></a></td>
                        <td class="center strong">{{ $country->gold }}</td>
                        <td class="center">{{ $country->silver }}</td>
                        <td class="center">{{ $country->bronze }}</td>
                        <td class="center strong">{{ $country->total }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Aucune médaille décernée pour l'instant.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
