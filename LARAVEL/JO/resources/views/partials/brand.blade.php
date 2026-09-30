{{-- Logo du site, dans l'en-tête des pages et des pages d'erreur (url() plutôt que route() : ces dernières n'en dépendent pas). --}}
<a href="{{ url('/') }}" class="brand">
    @include('partials.rings', ['size' => 44])
    <span>Jeux Olympiques</span>
</a>
