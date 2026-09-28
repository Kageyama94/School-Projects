<x-form-page
    :title="__('Modifier :name', ['name' => $teacher->full_name])"
    :action="route('admin.teachers.update', $teacher)"
    method="put">
    <x-person-fields :person="$teacher" />

    <div>
        <x-input-label value="Matières" />
        <div class="mt-1 space-y-1 max-h-64 overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-md p-2">
            @foreach ($subjects as $subject)
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                        @checked(collect(old('subjects', $teacher->subjects->pluck('id')))->contains($subject->id))>
                    {{ $subject->name }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('subjects')" class="mt-2" />
    </div>

    <x-slot name="after">
        <x-card id="transfer">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">Confier ses cours à un autre enseignant</h3>

            @if ($lessonSubjects->isEmpty())
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $teacher->full_name }} n'a aucun cours pour le moment.</p>
            @elseif ($replacements->isEmpty())
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Il n'y a pas d'autre enseignant à qui confier ses cours.</p>
            @else
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Pour remplacer cet enseignant, ou avant de lui retirer une matière. Le remplaçant doit enseigner la matière et être libre sur chaque créneau.
                </p>

                <form method="POST" action="{{ route('admin.teachers.transfer', $teacher) }}" class="mt-4 space-y-4"
                      onsubmit="return confirm(@js('Confier ces cours à l\'enseignant choisi ?'))">
                    @csrf

                    <div>
                        <x-input-label for="subject_id" value="Cours à confier" />
                        <x-select id="subject_id" name="subject_id">
                            <option value="">Tous ses cours ({{ $lessonSubjects->sum('lessons_count') }})</option>
                            @foreach ($lessonSubjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }} ({{ $subject->lessons_count }})</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="replacement_id" value="Enseignant remplaçant" />
                        <x-select id="replacement_id" name="replacement_id" required>
                            <option value="" disabled @selected(! old('replacement_id'))>— Choisir un enseignant —</option>
                            @foreach ($replacements as $replacement)
                                <option value="{{ $replacement->id }}" @selected(old('replacement_id') == $replacement->id)>
                                    {{ $replacement->full_name }} — {{ $replacement->subjects->pluck('name')->join(', ') ?: 'aucune matière' }}
                                </option>
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
