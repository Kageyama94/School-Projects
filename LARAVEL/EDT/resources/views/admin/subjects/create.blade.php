<x-form-page :title="__('Ajouter une matière')" :action="route('admin.subjects.store')" submit="Créer" width="max-w-xl">
    <div>
        <x-input-label for="name" value="Nom" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="color" value="Couleur" />
        <input id="color" name="color" type="color" class="mt-1 block h-10 w-20" value="{{ old('color', '#3b82f6') }}">
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>
</x-form-page>
