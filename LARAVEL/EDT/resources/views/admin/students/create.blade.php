<x-form-page :title="__('Ajouter un étudiant')" :action="route('admin.students.store')" submit="Créer le compte" :blocked="$groups->isEmpty()">
    <x-slot name="blockedMessage">
        Crée d'abord un <a href="{{ route('admin.groups.create') }}" class="underline">groupe</a> (rattaché à une licence et à un niveau) pour y inscrire des étudiants.
    </x-slot>

    <x-person-fields />

    <p class="text-xs text-gray-500 dark:text-gray-400">
        L'identifiant de connexion et le mot de passe initial (nom_prénom) seront générés automatiquement et affichés après la création du compte.
    </p>

    <div>
        <x-input-label for="group_id" value="Groupe (par licence, niveau puis nom)" />
        <x-group-select :groups="$groups" :selected="old('group_id')" />
        <x-input-error :messages="$errors->get('group_id')" class="mt-2" />
    </div>
</x-form-page>
