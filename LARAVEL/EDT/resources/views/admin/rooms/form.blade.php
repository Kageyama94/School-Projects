<x-form-page
    :title="$room->exists ? __('Modifier :name', ['name' => $room->name]) : __('Ajouter une salle')"
    :action="$room->exists ? route('admin.rooms.update', $room) : route('admin.rooms.store')"
    :method="$room->exists ? 'put' : 'post'"
    :submit="$room->exists ? 'Enregistrer' : 'Créer la salle'">
    <div>
        <x-input-label for="name" value="Nom" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $room->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="type" value="Type" />
        <x-select id="type" name="type" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $room->type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>
</x-form-page>
