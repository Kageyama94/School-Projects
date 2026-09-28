<x-page :title="__('Mon emploi du temps')">
    @if (! $group)
        <x-card class="text-gray-600 dark:text-gray-400">
            Ton compte n'est pas encore rattaché à un groupe. Contacte un administrateur.
        </x-card>
    @else
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 px-4 sm:px-0">
            <p class="text-gray-600 dark:text-gray-400">Groupe : <span class="font-medium">{{ $group->label }}</span></p>
            <a href="{{ route('calendar') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Exporter vers mon agenda (.ics)</a>
        </div>

        <x-weekly-schedule :lessons="$lessons" />
    @endif
</x-page>
