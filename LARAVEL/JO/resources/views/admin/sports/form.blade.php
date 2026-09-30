@extends('layouts.admin')

@section('title', $sport->exists ? 'Modifier un sport' : 'Nouveau sport')
@section('heading', $sport->exists ? 'Modifier « '.$sport->name.' »' : 'Nouveau sport')

@section('body')
<x-admin-form :model="$sport" routes="admin.sports" create-label="Créer le sport">
    <x-field name="name" label="Nom">
        <input type="text" name="name" value="{{ old('name', $sport->name) }}" required maxlength="100">
    </x-field>
    <x-field name="icon" label="Icône" hint="(un emoji, ex. 🏐)">
        <input type="text" name="icon" value="{{ old('icon', $sport->icon) }}" required maxlength="16">
    </x-field>
    <x-field name="description" label="Description" class="span-2">
        <input type="text" name="description" value="{{ old('description', $sport->description) }}" maxlength="500">
    </x-field>
    <label class="checkbox span-2">
        <input type="checkbox" name="two_bronzes" value="1" @checked(old('two_bronzes', $sport->two_bronzes))>
        Deux médailles de bronze par épreuve <span class="muted small">(sports de combat : judo, boxe, lutte, taekwondo)</span>
        <x-error field="two_bronzes" />
    </label>
</x-admin-form>
@endsection
