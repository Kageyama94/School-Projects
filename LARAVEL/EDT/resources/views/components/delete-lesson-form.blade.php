@props(['lesson'])

<form method="POST" action="{{ route('lessons.destroy', $lesson) }}" onsubmit="return confirm('Supprimer ce cours ?')" {{ $attributes }}>
    @csrf
    @method('delete')
    {{ $slot }}
</form>
