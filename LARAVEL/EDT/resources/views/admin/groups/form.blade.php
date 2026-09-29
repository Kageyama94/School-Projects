{{-- Avant d'enregistrer : prévient si des étudiants ou des enseignants sont concernés par un changement de licence ou de niveau. --}}
<x-form-page
    :title="$group->exists ? __('Modifier :name', ['name' => $group->label]) : __('Ajouter un groupe')"
    :action="$group->exists ? route('admin.groups.update', $group) : route('admin.groups.store')"
    :method="$group->exists ? 'put' : 'post'"
    :submit="$group->exists ? 'Enregistrer' : 'Créer le groupe'"
    :blocked="$licences->isEmpty()"
    x-data="{
        licence: {{ Js::from((string) $group->licence_id) }}, level: {{ Js::from($group->level?->value ?? '') }},
        students: {{ $studentsCount }}, teachers: {{ $teachersCount }},
        confirmChange(event) {
            const licenceChanged = this.$refs.licence.value !== this.licence;
            const warnings = [];
            if (this.students !== 0 && (licenceChanged || this.$refs.level.value !== this.level)) warnings.push(this.students + ' étudiant(s) de ce groupe changeront de licence ou de niveau avec lui.');
            if (this.teachers !== 0 && licenceChanged) warnings.push(this.teachers + ' enseignant(s) qui y ont cours recevront aussi la nouvelle licence.');
            if (warnings.length && ! confirm(warnings.join(' ') + ' Continuer ?')) event.preventDefault();
        },
    }"
    x-on:submit="confirmChange($event)">
    <x-slot name="blockedMessage">
        Crée d'abord une <a href="{{ route('admin.licences.create') }}" class="underline">licence</a> : chaque groupe appartient à une licence.
    </x-slot>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="licence_id" value="Licence" />
            <x-select id="licence_id" name="licence_id" x-ref="licence" required>
                @foreach ($licences as $licence)
                    <option value="{{ $licence->id }}" @selected(old('licence_id', $group->licence_id) == $licence->id)>{{ $licence->name }}</option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('licence_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="level" value="Niveau" />
            <x-select id="level" name="level" x-ref="level" required>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(old('level', $group->level?->value) === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('level')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="name" value="Nom du groupe (ex. Groupe A)" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $group->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
</x-form-page>
