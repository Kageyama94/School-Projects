<x-page :title="__('Mon emploi du temps')">
    @if (! $teacher)
        <x-card class="text-gray-600 dark:text-gray-400">
            Ton compte n'est pas encore rattaché à une fiche enseignant. Contacte un administrateur.
        </x-card>
    @elseif ($subjects->isEmpty())
        <x-card class="text-gray-600 dark:text-gray-400">
            Aucune matière ne t'est encore assignée. Contacte un administrateur.
        </x-card>
    @elseif ($groups->isEmpty())
        <x-card class="text-gray-600 dark:text-gray-400">
            Aucun groupe n'existe encore. Contacte un administrateur.
        </x-card>
    @else
        @php
            $hasLessonErrors = $errors->hasAny(['slot', 'day_of_week', 'group_id', 'subject_id', 'room_id']);
            $initialGroup = $groups->firstWhere('id', (int) old('group_id')) ?? $groups->first();
            $initial = [
                'group' => $initialGroup->id,
                'licence' => $initialGroup->licence_id,
                'day' => old('day_of_week') !== null ? (int) old('day_of_week') : null,
                'slot' => old('slot') !== null ? (int) old('slot') : null,
                'room' => (string) old('room_id', ''),
            ];
        @endphp

        <div x-data="teacherScheduler(@js($scheduler), @js($initial))" class="space-y-6">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3 px-4 sm:px-0">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200">Ma semaine</h3>
                    <a href="{{ route('calendar') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Exporter vers mon agenda (.ics)</a>
                </div>
                <x-weekly-schedule :lessons="$myLessons" deletable />
            </div>

            <x-card class="print:hidden">
                <div class="flex flex-wrap items-center justify-between mb-2 gap-4">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                        Emploi du temps — <span x-text="groupName(selectedGroupId)"></span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <x-select compact aria-label="Licence" @change="selectLicence(Number($event.target.value))">
                            <template x-for="licence in licences" :key="licence.id">
                                <option :value="licence.id" :selected="licence.id === selectedLicenceId" x-text="licence.name"></option>
                            </template>
                        </x-select>
                        <x-select compact aria-label="Groupe" @change="selectedGroupId = Number($event.target.value)">
                            <template x-for="group in groupsOfLicence()" :key="group.id">
                                <option :value="group.id" :selected="group.id === selectedGroupId" x-text="group.level + ' · ' + group.name + ' (' + group.size + ' étudiants)'"></option>
                            </template>
                        </x-select>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Tes cours (en couleur) et les créneaux déjà pris par ce groupe avec un autre enseignant (« Autres cours ») apparaissent sur le même tableau. Clique sur un créneau libre pour ajouter un cours.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr>
                                <th class="p-1"></th>
                                @foreach ($days as $day)
                                    <th class="p-1 text-gray-600 dark:text-gray-400 font-medium">{{ $day->label() }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($slots as $slotIndex => $slot)
                                <tr>
                                    <td class="p-1 text-gray-500 dark:text-gray-400 whitespace-nowrap text-center align-middle">{{ $slot['start'] }}-{{ $slot['end'] }}</td>
                                    @foreach ($days as $day)
                                        @php
                                            $cellKey = $day->value.'-'.$slot['start'];
                                            $slotAllowed = array_key_exists($slotIndex, $slotsByDay[$day->value]);
                                            $teacherLesson = $slotAllowed ? $teacherByCell->get($cellKey) : null;
                                        @endphp
                                        <td class="p-1 align-top">
                                            @if (! $slotAllowed)
                                                <div class="rounded-md p-2 text-center text-gray-300 dark:text-gray-700">—</div>
                                            @elseif ($teacherLesson)
                                                <div x-show="selectedGroupId === {{ $teacherLesson->group_id }}" x-cloak
                                                     class="rounded-md p-2 text-gray-900 dark:text-gray-100" style="background-color: {{ $teacherLesson->subject->color }}22; border-left: 3px solid {{ $teacherLesson->subject->color }};">
                                                    <div class="font-medium">{{ $teacherLesson->subject->name }}</div>
                                                    <div class="opacity-70">{{ $teacherLesson->group->name }}{{ $teacherLesson->room ? ' · '.$teacherLesson->room->name : '' }}</div>
                                                    <x-delete-lesson-form :lesson="$teacherLesson">
                                                        <input type="hidden" name="group_id" :value="selectedGroupId">
                                                        <button type="submit" class="mt-1 underline opacity-90 hover:opacity-100">Retirer</button>
                                                    </x-delete-lesson-form>
                                                </div>
                                                <div x-show="selectedGroupId !== {{ $teacherLesson->group_id }}" x-cloak
                                                     class="rounded-md p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                                    <div class="font-medium">Occupé : {{ $teacherLesson->subject->name }} ({{ $teacherLesson->group->label }})</div>
                                                </div>
                                            @else
                                                <div x-show="groupBusyAt('{{ $cellKey }}')" x-cloak
                                                     class="rounded-md p-2 bg-gray-100/50 dark:bg-gray-700/30 text-gray-400 dark:text-gray-500 text-center italic">
                                                    Autres cours
                                                </div>
                                                <button type="button" x-show="! groupBusyAt('{{ $cellKey }}')" x-cloak
                                                    aria-label="Ajouter un cours le {{ $day->label() }} de {{ $slot['start'] }} à {{ $slot['end'] }}"
                                                    class="w-full rounded-md p-2 text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40"
                                                    @click="pick({{ $day->value }}, {{ $slotIndex }})">Libre</button>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-modal name="add-lesson" :show="$hasLessonErrors" max-width="md" focusable>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ajouter un cours</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <span x-text="dayLabels[selectedDay] ?? ''"></span> · <span x-text="(slots[selectedSlot]?.start ?? '') + ' - ' + (slots[selectedSlot]?.end ?? '')"></span> · <span x-text="groupName(selectedGroupId)"></span>
                    </p>

                    <form method="POST" action="{{ route('lessons.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="day_of_week" :value="selectedDay">
                        <input type="hidden" name="slot" :value="selectedSlot">
                        <input type="hidden" name="group_id" :value="selectedGroupId">

                        <div>
                            <x-input-label for="subject_id" value="Matière" />
                            <x-select id="subject_id" name="subject_id" required>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div>
                            <x-input-label for="room_id" value="Salle" />
                            <x-select id="room_id" name="room_id" @change="selectedRoomId = $event.target.value">
                                <option value="">—</option>
                                <template x-for="room in freeRooms()" :key="room.id">
                                    <option :value="room.id" :selected="String(room.id) === selectedRoomId" :disabled="roomTooSmall(room)" x-text="room.label + (roomTooSmall(room) ? ' — trop petite' : '')"></option>
                                </template>
                            </x-select>
                            <p x-show="freeRooms().length === 0" x-cloak class="mt-1 text-xs text-amber-700 dark:text-amber-400">Aucune salle libre à ce créneau.</p>
                        </div>

                        <x-input-error :messages="$errors->get('slot')" class="mt-2" />
                        <x-input-error :messages="$errors->get('day_of_week')" class="mt-2" />
                        <x-input-error :messages="$errors->get('group_id')" class="mt-2" />
                        <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
                        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />

                        <div class="flex justify-end gap-3">
                            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'add-lesson')">Annuler</x-secondary-button>
                            <x-primary-button>Ajouter ce cours</x-primary-button>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>
    @endif

    <script src="{{ asset('js/teacher-scheduler.js') }}"></script>
</x-page>
