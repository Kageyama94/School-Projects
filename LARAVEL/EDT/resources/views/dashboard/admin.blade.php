<x-page :title="__('Tableau de bord')">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach ([
            'groupes' => $counts['groups'],
            'matières' => $counts['subjects'],
            'enseignants' => $counts['teachers'],
            'étudiants' => $counts['students'],
            'salles' => $counts['rooms'],
            'étudiants sans groupe' => $counts['students_without_group'],
        ] as $label => $count)
            @php($warn = $label === 'étudiants sans groupe' && $count > 0)
            <div @class([
                'rounded-lg shadow-sm p-4 text-center',
                'bg-white dark:bg-gray-800' => ! $warn,
                'bg-amber-100 dark:bg-amber-900/40' => $warn,
            ])>
                <div @class([
                    'text-2xl font-semibold',
                    'text-gray-900 dark:text-gray-100' => ! $warn,
                    'text-amber-800 dark:text-amber-300' => $warn,
                ])>{{ $count }}</div>
                <div @class([
                    'text-xs uppercase tracking-wide',
                    'text-gray-500 dark:text-gray-400' => ! $warn,
                    'text-amber-800 dark:text-amber-300' => $warn,
                ])>{{ $label }}</div>
            </div>
        @endforeach
    </div>
</x-page>
