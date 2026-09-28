<x-form-page
    :title="$licence->exists ? __('Modifier :name', ['name' => $licence->name]) : __('Ajouter une licence')"
    :action="$licence->exists ? route('admin.licences.update', $licence) : route('admin.licences.store')"
    :method="$licence->exists ? 'put' : 'post'"
    :submit="$licence->exists ? 'Enregistrer' : 'Créer la licence'">
    <div>
        <x-input-label for="name" value="Nom (ex. Informatique)" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $licence->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
</x-form-page>
