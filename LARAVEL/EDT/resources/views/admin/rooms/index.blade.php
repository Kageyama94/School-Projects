<x-page :title="__('Salles')">
    <x-card>
        <x-list-header title="Salles" :create-route="route('admin.rooms.create')" create-label="+ Ajouter une salle" />
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-gray-500 dark:text-gray-400">
                    <th class="py-1">Nom</th>
                    <th class="py-1">Type</th>
                    <th class="py-1">Capacité</th>
                    <th class="py-1">Cours</th>
                    <th class="py-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2 text-gray-900 dark:text-gray-100">{{ $room->name }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $room->type->label() }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $room->capacity ?? '—' }}</td>
                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ $room->lessons_count }}</td>
                        <td class="py-2 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.rooms.show', $room) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Emploi du temps</a>
                            <a href="{{ route('admin.rooms.edit', $room) }}" class="text-xs underline text-gray-500 dark:text-gray-400">Modifier</a>
                            <x-confirm-button :action="route('admin.rooms.destroy', $room)" confirm="Supprimer cette salle ?" method="delete" danger>Supprimer</x-confirm-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-2 text-gray-400">Aucune salle.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-page>
