<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-danger">Supprimer</button>
</form>
