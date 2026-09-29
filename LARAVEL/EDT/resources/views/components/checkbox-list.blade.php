@props(['name', 'label', 'items', 'selected' => [], 'empty' => 'Aucun élément.'])

{{-- Liste de cases à cocher envoyée sous « name[] » ; les valeurs cochées suivent old() après une erreur. --}}
<div>
    <x-input-label :value="$label" />
    <div class="mt-1 space-y-1 max-h-64 overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-md p-2">
        @forelse ($items as $item)
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $item->id }}"
                    @checked(collect(old($name, $selected))->contains($item->id))>
                {{ $item->name }}
            </label>
        @empty
            <p class="text-sm text-gray-400">{{ $empty }}</p>
        @endforelse
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
