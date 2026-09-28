@props(['lessons', 'deletable' => false])

@php
    $byDay = $lessons->groupBy(fn ($lesson) => $lesson->day_of_week->value);
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 print:grid-cols-3">
    @foreach (\App\Enums\DayOfWeek::schoolDays() as $day)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 print:shadow-none print:border print:border-gray-300 print:break-inside-avoid">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ $day->label() }}</h3>

            @forelse ($byDay->get($day->value, collect()) as $lesson)
                <div class="mb-2 rounded-md p-2 text-sm relative" style="background-color: {{ $lesson->subject->color }}22; border-left: 3px solid {{ $lesson->subject->color }};">
                    @if ($deletable)
                        <x-delete-lesson-form :lesson="$lesson" class="absolute top-1 right-1 print:hidden">
                            <button type="submit" class="text-gray-400 hover:text-red-600 leading-none" title="Supprimer ce cours" aria-label="Supprimer ce cours">&times;</button>
                        </x-delete-lesson-form>
                    @endif
                    <div class="font-medium text-gray-900 dark:text-gray-100 {{ $deletable ? 'pr-4' : '' }}">{{ $lesson->subject->name }}</div>
                    <div class="text-gray-600 dark:text-gray-400 text-xs">
                        {{ $lesson->start_time->format('H:i') }} - {{ $lesson->end_time->format('H:i') }}
                    </div>
                    @if ($lesson->relationLoaded('group') && $lesson->group)
                        <div class="text-gray-600 dark:text-gray-400 text-xs">{{ $lesson->group->label }}</div>
                    @endif
                    @if ($lesson->relationLoaded('teacher') && $lesson->teacher)
                        <div class="text-gray-600 dark:text-gray-400 text-xs">{{ $lesson->teacher->full_name }}</div>
                    @endif
                    @if ($lesson->relationLoaded('room') && $lesson->room)
                        <div class="text-gray-600 dark:text-gray-400 text-xs">{{ $lesson->room->name }}</div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500">—</p>
            @endforelse
        </div>
    @endforeach
</div>
