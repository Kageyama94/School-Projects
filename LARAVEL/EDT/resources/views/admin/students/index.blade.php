<x-page :title="__('Étudiants')">
    <x-card>
        <x-list-header title="Étudiants" :create-route="route('admin.students.create')" create-label="+ Ajouter un étudiant" />
        <x-search-form :action="route('admin.students.index')" :search="$search" placeholder="Rechercher un nom ou un identifiant" />
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-gray-500 dark:text-gray-400">
                    <th class="py-1">Nom</th>
                    <th class="py-1">Identifiant</th>
                    <th class="py-1">Licence</th>
                    <th class="py-1">Niveau</th>
                    <th class="py-1">Groupe</th>
                    <th class="py-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2 text-gray-900 dark:text-gray-100">{{ $student->full_name }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $student->user?->identifiant ?? '—' }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $student->group?->licence->name ?? '—' }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $student->group?->level->short() ?? '—' }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $student->group?->name ?? '—' }}</td>
                        <td class="py-2 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.students.edit', $student) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Modifier</a>
                            @if ($student->user)
                                <x-confirm-button :action="route('admin.users.password.reset', $student->user)" confirm="Réinitialiser le mot de passe de cet étudiant ?">Réinitialiser le mdp</x-confirm-button>
                            @endif
                            <x-confirm-button :action="route('admin.students.destroy', $student)" confirm="Supprimer cet étudiant et son compte ?" method="delete" danger>Supprimer</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="6">Aucun étudiant.</x-empty-row>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $students->links() }}</div>
    </x-card>
</x-page>
