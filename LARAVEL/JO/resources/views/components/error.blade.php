@props(['field'])
{{-- Affiche l'erreur sous le champ et la retire du bandeau en haut de page (voir layouts/app). --}}
@php(request()->attributes->set('inline_errors', [...request()->attributes->get('inline_errors', []), $field]))
@error($field)
    <span class="field-error" role="alert">{{ $message }}</span>
@enderror
