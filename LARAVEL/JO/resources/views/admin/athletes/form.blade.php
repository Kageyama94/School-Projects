@extends('layouts.admin')

@section('title', $athlete->exists ? 'Modifier un athlète' : 'Nouvel athlète')
@section('heading', $athlete->exists ? 'Modifier '.$athlete->fullName() : 'Nouvel athlète')

@section('body')
<x-admin-form :model="$athlete" routes="admin.athletes" create-label="Ajouter l'athlète">
    <x-field name="first_name" label="Prénom">
        <input type="text" name="first_name" value="{{ old('first_name', $athlete->first_name) }}" required maxlength="100">
    </x-field>
    <x-field name="last_name" label="Nom">
        <input type="text" name="last_name" value="{{ old('last_name', $athlete->last_name) }}" required maxlength="100">
    </x-field>
    <x-field name="sport_id" label="Sport">
        <select name="sport_id" required>
            @foreach ($sports as $sport)
                <option value="{{ $sport->id }}" @selected(old('sport_id', $athlete->sport_id) == $sport->id)>{{ $sport->icon }} {{ $sport->name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="country_id" label="Pays">
        <select name="country_id" required>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $athlete->country_id) == $country->id)>{{ $country->name }} ({{ $country->code }})</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="gender" label="Genre">
        <select name="gender" required>
            @foreach (\App\Enums\Gender::forAthletes() as $gender)
                <option value="{{ $gender->value }}" @selected(old('gender', $athlete->gender?->value) === $gender->value)>{{ $gender->singularLabel() }}</option>
            @endforeach
        </select>
    </x-field>
</x-admin-form>
@endsection
