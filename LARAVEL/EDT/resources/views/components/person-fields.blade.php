@props(['person' => null])

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="first_name" value="Prénom" />
        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $person?->first_name)" required />
        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="last_name" value="Nom" />
        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $person?->last_name)" required />
        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
    </div>
</div>

