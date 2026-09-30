@extends('layouts.admin')

@section('title', $country->exists ? 'Modifier un pays' : 'Nouveau pays')
@section('heading', $country->exists ? 'Modifier « '.$country->name.' »' : 'Nouveau pays')

@section('body')
<x-admin-form :model="$country" routes="admin.countries" create-label="Ajouter le pays">
    <x-field name="iso" label="Pays" hint="(détermine le drapeau)" class="span-2">
        <span class="flag-picker">
            @php $currentIso = old('iso', $country->iso); @endphp
            <img src="{{ $currentIso ? asset('img/flags/'.$currentIso.'.png') : '' }}" alt="" class="flag" id="flag-preview" @if (! $currentIso) hidden @endif>
            <select name="iso" id="iso" required>
                <option value="">— Choisir —</option>
                @foreach ($world as $iso => $name)
                    <option value="{{ $iso }}" data-name="{{ $name }}" @selected($currentIso === $iso)>{{ $name }}</option>
                @endforeach
            </select>
        </span>
    </x-field>
    <x-field name="name" label="Nom affiché">
        <input type="text" name="name" id="name" value="{{ old('name', $country->name) }}" required maxlength="100">
    </x-field>
    <x-field name="code" label="Code CIO" hint="(3 lettres, ex. FRA)">
        <input type="text" name="code" value="{{ old('code', $country->code) }}" required minlength="3" maxlength="3" style="text-transform: uppercase">
    </x-field>
</x-admin-form>
@endsection

@push('scripts')
<script>
    // Aperçu du drapeau, et nom pré-rempli tant qu'il n'a pas été saisi à la main.
    const iso = document.getElementById('iso');
    const name = document.getElementById('name');
    const preview = document.getElementById('flag-preview');
    iso.addEventListener('change', () => {
        const option = iso.selectedOptions[0];
        if (!option.value) return;
        preview.src = @js(asset('img/flags')) + '/' + option.value + '.png';
        preview.hidden = false;
        if (!name.value || name.dataset.auto) {
            name.value = option.dataset.name;
            name.dataset.auto = '1';
        }
    });
    name.addEventListener('input', () => delete name.dataset.auto);
</script>
@endpush
