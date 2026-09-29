<x-form-page :title="__('Ajouter un enseignant')" :action="route('admin.teachers.store')" submit="Créer le compte">
    <x-person-fields />

    <x-checkbox-list name="subjects" label="Matières" :items="$subjects" empty="Aucune matière." />
    <x-checkbox-list name="licences" label="Licences (groupes où il peut programmer des cours)" :items="$licences" empty="Aucune licence." />

    <p class="text-xs text-gray-500 dark:text-gray-400">
        L'identifiant de connexion et le mot de passe initial (nom_prénom) seront générés automatiquement et affichés après la création du compte.
    </p>
</x-form-page>
