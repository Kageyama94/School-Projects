<x-page :title="__('Licences et groupes')">
    <div class="flex flex-wrap items-center justify-between gap-2 px-4 sm:px-0">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Une licence est une filière (Informatique, Mathématiques…). Chaque groupe appartient à une licence et à un niveau (L1, L2, L3, M1, M2).
        </p>
        <a href="{{ route('admin.licences.create') }}" class="text-sm underline text-gray-600 dark:text-gray-400">+ Ajouter une licence</a>
    </div>

    @forelse ($licences as $licence)
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $licence->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $licence->groups_count }} groupe(s) · {{ $licence->students_count }} étudiant(s)</p>
                </div>
                <div class="space-x-3 whitespace-nowrap">
                    <a href="{{ route('admin.groups.create', ['licence_id' => $licence->id]) }}" class="text-xs underline text-gray-600 dark:text-gray-400">+ Groupe</a>
                    <a href="{{ route('admin.licences.edit', $licence) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Modifier la licence</a>
                    <x-confirm-button :action="route('admin.licences.destroy', $licence)" confirm="Supprimer cette licence ?" method="delete" danger>Supprimer la licence</x-confirm-button>
                </div>
            </div>

            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-gray-500 dark:text-gray-400">
                        <th class="py-1">Niveau</th>
                        <th class="py-1">Groupe</th>
                        <th class="py-1">Étudiants</th>
                        <th class="py-1">Cours</th>
                        <th class="py-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($licence->groups as $group)
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="py-2 text-gray-600 dark:text-gray-400">{{ $group->level->short() }}</td>
                            <td class="py-2 text-gray-900 dark:text-gray-100">{{ $group->name }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-400">{{ $group->students_count }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-400">{{ $group->lessons_count }}</td>
                            <td class="py-2 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('admin.groups.show', $group) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Emploi du temps</a>
                                <a href="{{ route('admin.groups.edit', $group) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Modifier</a>
                                <x-confirm-button :action="route('admin.groups.destroy', $group)" confirm="Supprimer ce groupe ?" method="delete" danger>Supprimer</x-confirm-button>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row colspan="5">Aucun groupe dans cette licence.</x-empty-row>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    @empty
        <x-card class="text-gray-600 dark:text-gray-400">
            Aucune licence pour le moment. Commence par <a href="{{ route('admin.licences.create') }}" class="underline">ajouter une licence</a>, puis ses groupes.
        </x-card>
    @endforelse
</x-page>
