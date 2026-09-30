@props(['name', 'label', 'hint' => null])
{{-- Champ de formulaire : libellé, indication facultative, le champ lui-même (slot) et son erreur. --}}
<label {{ $attributes }}>
    {{ $label }}
    @if ($hint)
        <span class="muted small">{{ $hint }}</span>
    @endif
    {{ $slot }}
    <x-error :field="$name" />
</label>
