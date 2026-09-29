<x-form-page
    :title="__('Modifier :name', ['name' => $teacher->full_name])"
    :action="route('admin.teachers.update', $teacher)"
    method="put">
    <x-person-fields :person="$teacher" />

    <x-checkbox-list name="subjects" label="Matières" :items="$subjects" :selected="$teacher->subjects->modelKeys()" empty="Aucune matière." />
    <x-checkbox-list name="licences" label="Licences (groupes où il peut programmer des cours)" :items="$licences" :selected="$teacher->licences->modelKeys()" empty="Aucune licence." />

    <x-slot name="after">
        <x-card id="transfer">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">Confier ses cours à un autre enseignant</h3>

            @if ($lessonSubjects->isEmpty())
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $teacher->full_name }} n'a aucun cours pour le moment.</p>
            @elseif ($replacements->isEmpty())
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aucun autre enseignant n'enseigne ses matières dans les licences concernées. Assigne-les d'abord à un enseignant.</p>
            @else
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Pour remplacer cet enseignant, ou avant de lui retirer une matière ou une licence. Seuls les enseignants qui ont la matière et les licences concernées sont proposés ; ils doivent aussi être libres sur chaque créneau.
                </p>

                <form method="POST" action="{{ route('admin.teachers.transfer', $teacher) }}" class="mt-4 space-y-4"
                      x-data="{
                          subject: @js((string) old('subject_id', '')),
                          {{-- Un remplaçant est grisé s'il ne peut pas reprendre la sélection (takes vient de TransferLessons::candidates). --}}
                          blocked(takes) { return this.subject === '' ? ! takes.all : ! takes.subjects.includes(Number(this.subject)) },
                          reason() { return this.subject === '' ? ' (ne peut pas reprendre tous ses cours)' : ' (pas cette matière ou pas ses licences)' },
                      }"
                      onsubmit="return confirm(@js('Confier ces cours à l\'enseignant choisi ?'))">
                    @csrf

                    <div>
                        <x-input-label for="subject_id" value="Cours à confier" />
                        <x-select id="subject_id" name="subject_id" x-model="subject"
                            @change="$nextTick(() => { if ($refs.replacement.selectedOptions[0]?.disabled) $refs.replacement.value = '' })">
                            <option value="">Tous ses cours ({{ $lessonSubjects->sum('lessons_count') }})</option>
                            @foreach ($lessonSubjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }} ({{ $subject->lessons_count }})</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="replacement_id" value="Enseignant remplaçant" />
                        <x-select id="replacement_id" name="replacement_id" required x-ref="replacement">
                            <option value="" disabled @selected(! old('replacement_id'))>— Choisir un enseignant —</option>
                            @foreach ($replacements as $replacement)
                                @php($label = $replacement->full_name.' — '.$replacement->subjects->pluck('name')->join(', '))
                                <option value="{{ $replacement->id }}" @selected(old('replacement_id') == $replacement->id)
                                    x-data="{ label: @js($label), takes: @js($replacement->takes) }"
                                    x-bind:disabled="blocked(takes)"
                                    x-text="label + (blocked(takes) ? reason() : '')">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('replacement_id')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Confier les cours</x-primary-button>
                    </div>
                </form>
            @endif
        </x-card>
    </x-slot>
</x-form-page>
