@extends('layouts.admin')

@section('title', $event->exists ? 'Modifier une épreuve' : 'Nouvelle épreuve')
@section('heading', $event->exists ? 'Modifier « '.$event->name.' »' : 'Nouvelle épreuve')

@section('body')
<x-admin-form :model="$event" routes="admin.events" create-label="Créer l'épreuve">
    <x-field name="name" label="Nom de l'épreuve" class="span-2">
        <input type="text" name="name" value="{{ old('name', $event->name) }}" required maxlength="150" placeholder="ex. 100 m, Finale du tournoi…">
    </x-field>
    <x-field name="sport_id" label="Sport">
        <select name="sport_id" required>
            @foreach ($sports as $sport)
                <option value="{{ $sport->id }}" @selected(old('sport_id', $event->sport_id) == $sport->id)>{{ $sport->icon }} {{ $sport->name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="gender" label="Catégorie">
        <select name="gender" required>
            @foreach (\App\Enums\Gender::cases() as $gender)
                <option value="{{ $gender->value }}" @selected(old('gender', $event->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="venue_id" label="Site">
        <select name="venue_id" required>
            @foreach ($venues as $venue)
                <option value="{{ $venue->id }}" @selected(old('venue_id', $event->venue_id) == $venue->id)>{{ $venue->name }} ({{ $venue->city }}, @number($venue->capacity) places)</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="starts_at" label="Date et heure" hint="(heure de Paris)">
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required>
    </x-field>
    <x-field name="price" label="Prix (€)">
        <input type="number" name="price" min="0" max="10000" value="{{ old('price', $event->price) }}" required>
    </x-field>
    <x-field name="capacity" label="Nombre de places">
        <input type="number" name="capacity" min="1" max="200000" value="{{ old('capacity', $event->capacity) }}" required>
    </x-field>
    <label class="checkbox span-2">
        <input type="checkbox" name="team" value="1" @checked(old('team', $event->team))>
        Épreuve par équipes <span class="muted small">(la médaille est attribuée à un pays, pas à un athlète)</span>
        <x-error field="team" />
    </label>
</x-admin-form>
@endsection
