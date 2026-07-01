@extends('modele')

@section('content')

<h1>Liste des pizzas</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<table border="1" cellpadding="5">
    <tr>
        <th>Nom</th>
        <th>Prix</th>
        <th>Description</th>
        <th>Actions
            <a href="{{ route('add') }}">
                <button type="button">Ajouter</button>
            </a>
        </th>
    </tr>
    @foreach($pizzas as $pizza)
    <tr>
        <td>{{ $pizza->name }}</td>
        <td>{{ number_format($pizza->price, 2) }} €</td>
        <td>{{ $pizza->description }}</td>
        <td>
            <a href="{{ route('edit', $pizza->id) }}">
                <button type="button">Modifier</button>
            </a>

            <form action="{{ route('destroy', $pizza->id) }}" method="post" style="display:inline"
                  onsubmit="return confirm('Supprimer {{ addslashes($pizza->name) }} ?')">
                @csrf
                @method('DELETE')
                <input type="submit" value="Supprimer">
            </form>
        </td>
    </tr>
    @endforeach
</table>

{{ $pizzas->links() }}

<br>
<a href="{{ route('admin') }}">
    <button type="button">Retour au tableau de bord</button>
</a>

@endsection
