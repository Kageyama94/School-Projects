<x-form-page :title="__('Ajouter un enseignant')" :action="route('admin.teachers.store')" submit="Créer le compte">
    <x-person-fields />

    <p class="text-xs text-gray-500 dark:text-gray-400">
        L'identifiant de connexion et le mot de passe initial (nom_prénom) seront générés automatiquement et affichés après la création du compte.
        Tu lui assigneras ses matières ensuite, depuis « Modifier » dans la liste des enseignants.
    </p>
</x-form-page>
