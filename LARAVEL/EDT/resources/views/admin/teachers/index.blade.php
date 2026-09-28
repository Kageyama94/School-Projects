<x-page :title="__('Enseignants')">
    <x-card>
        <x-list-header title="Enseignants" :create-route="route('admin.teachers.create')" create-label="+ Ajouter un enseignant" />
        <x-search-form :action="route('admin.teachers.index')" :search="$search" placeholder="Rechercher un nom ou un prénom" />
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-gray-500 dark:text-gray-400">
                    <th class="py-1">Nom</th>
                    <th class="py-1">Identifiant</th>
                    <th class="py-1">Matières</th>
                    <th class="py-1">Cours</th>
                    <th class="py-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teachers as $teacher)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2 text-gray-900 dark:text-gray-100">{{ $teacher->full_name }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $teacher->user?->identifiant ?? '—' }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $teacher->subjects->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $teacher->lessons_count }}</td>
                        <td class="py-2 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Emploi du temps</a>
                            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Modifier</a>
                            @if ($teacher->user)
                                <x-confirm-button :action="route('admin.users.password.reset', $teacher->user)" confirm="Réinitialiser le mot de passe de cet enseignant ?">Réinitialiser le mdp</x-confirm-button>
                            @endif
                            <x-confirm-button
                                :action="route('admin.teachers.destroy', $teacher)"
                                :confirm="'Supprimer cet enseignant, son compte et ses '.$teacher->lessons_count.' cours ? Pour les conserver, confie-les d\'abord à un autre enseignant depuis « Modifier ».'"
                                method="delete"
                                danger>Supprimer</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-2 text-gray-400">Aucun enseignant.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $teachers->links() }}</div>
    </x-card>

    <x-card>
        <x-list-header title="Matières" :create-route="route('admin.subjects.create')" create-label="+ Ajouter une matière" />
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-gray-500 dark:text-gray-400">
                    <th class="py-1">Nom</th>
                    <th class="py-1">Couleur</th>
                    <th class="py-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subjects as $subject)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2 text-gray-900 dark:text-gray-100">{{ $subject->name }}</td>
                        <td class="py-2">
                            <span class="inline-block w-4 h-4 rounded-full align-middle" style="background-color: {{ $subject->color }}"></span>
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            @if ($subject->lessons_count === 0)
                                <x-confirm-button :action="route('admin.subjects.destroy', $subject)" confirm="Supprimer cette matière ?" method="delete" danger>Supprimer</x-confirm-button>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $subject->lessons_count }} cours</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-2 text-gray-400">Aucune matière.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $subjects->links() }}</div>
    </x-card>
</x-page>
