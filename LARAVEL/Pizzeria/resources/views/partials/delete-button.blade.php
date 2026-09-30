<form method="POST" action="{{ $action }}" style="display:inline" onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
    <input type="submit" value="Supprimer" style="background:#7f8c8d">
</form>
