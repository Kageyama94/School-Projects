@props(['groups', 'selected' => null, 'nullable' => false])

<x-select id="group_id" name="group_id" :required="! $nullable">
    <option value="" @disabled(! $nullable) @selected(! $selected)>{{ $nullable ? '— Aucun groupe —' : '— Choisir un groupe —' }}</option>
    @foreach ($groups as $licenceName => $licenceGroups)
        <optgroup label="{{ $licenceName }}">
            @foreach ($licenceGroups as $group)
                <option value="{{ $group->id }}" @selected($selected == $group->id)>{{ $group->level->short() }} · {{ $group->name }}</option>
            @endforeach
        </optgroup>
    @endforeach
</x-select>
