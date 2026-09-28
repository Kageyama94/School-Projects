<x-form-page
    :title="__('Modifier :name', ['name' => $student->full_name])"
    :action="route('admin.students.update', $student)"
    method="put">
    <x-person-fields :person="$student" />

    <div>
        <x-input-label for="group_id" value="Groupe (par licence, niveau puis nom)" />
        <x-group-select :groups="$groups" :selected="old('group_id', $student->group_id)" nullable />
        <x-input-error :messages="$errors->get('group_id')" class="mt-2" />
    </div>
</x-form-page>
