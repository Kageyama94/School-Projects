@extends('layouts.app')

@section('title', 'Sports')

@section('content')
<section class="page-head">
    <div class="wrap">
        <h1>Les sports</h1>
        <p>{{ $sports->count() }} disciplines au programme olympique.</p>
    </div>
</section>

<section class="wrap sport-grid">
    @foreach ($sports as $sport)
        <a href="{{ route('sports.show', $sport) }}" class="sport-card">
            <div class="sport-icon">{{ $sport->icon }}</div>
            <h3>{{ $sport->name }}</h3>
            <p>{{ $sport->description }}</p>
            <div class="sport-meta">{{ $sport->events_count }} épreuves · {{ $sport->athletes_count }} athlètes</div>
        </a>
    @endforeach
</section>
@endsection
