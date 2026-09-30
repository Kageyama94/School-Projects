@props(['label'])

<div class="form-row">
    <label>{{ $label }}</label>
    {{ $slot }}
</div>
