@props(['model', 'routes', 'createLabel'])
{{-- Formulaire de création ou de modification d'une ressource d'administration ($routes : « admin.sports »…). --}}
<form method="POST"
      action="{{ $model->exists ? route("$routes.update", $model) : route("$routes.store") }}"
      {{ $attributes->merge(['class' => 'card form form-grid']) }}>
    @csrf
    @if ($model->exists)
        @method('PUT')
    @endif

    {{ $slot }}

    <div class="span-2 form-actions">
        <a href="{{ route("$routes.index") }}" class="btn btn-ghost-dark">Annuler</a>
        <button class="btn btn-primary">{{ $model->exists ? 'Enregistrer' : $createLabel }}</button>
    </div>
</form>
