@extends('layouts.admin')

@section('title', $venue->exists ? 'Modifier un site' : 'Nouveau site')
@section('heading', $venue->exists ? 'Modifier « '.$venue->name.' »' : 'Nouveau site')

@section('body')
<x-admin-form :model="$venue" routes="admin.venues" create-label="Créer le site">
    <x-field name="name" label="Nom" class="span-2">
        <input type="text" name="name" value="{{ old('name', $venue->name) }}" required maxlength="150">
    </x-field>
    <x-field name="city" label="Ville">
        <input type="text" name="city" value="{{ old('city', $venue->city) }}" required maxlength="100">
    </x-field>
    <x-field name="capacity" label="Capacité (places)">
        <input type="number" name="capacity" value="{{ old('capacity', $venue->capacity) }}" required min="1" max="200000">
    </x-field>
</x-admin-form>
@endsection
