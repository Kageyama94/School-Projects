@extends('modele')

@section('content')

<h1>Liste des pizzas</h1>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<table>
    <tr>
        <th>Nom</th>
        <th>Prix</th>
        <th>Description</th>
        <th style="white-space:nowrap">
            Actions &nbsp;
            <a href="{{ route('admin.pizza.create') }}"><button type="button" style="font-size:.8rem; padding:4px 10px">+ Ajouter</button></a>
        </th>
    </tr>
    @foreach($pizzas as $pizza)
    <tr>
        <td>{{ $pizza->name }}</td>
        <td>{{ number_format($pizza->price, 2, ',', ' ') }} €</td>
        <td>{{ $pizza->description }}</td>
        <td style="white-space:nowrap">
            <a href="{{ route('admin.pizza.edit', $pizza->id) }}"><button type="button">Modifier</button></a>
            <form action="{{ route('admin.pizza.destroy', $pizza->id) }}" method="post" style="display:inline"
                  onsubmit="return confirm('Supprimer ' + @js($pizza->name) + ' ?')">
                @csrf
                @method('DELETE')
                <input type="submit" value="Supprimer" style="background:#7f8c8d">
            </form>
        </td>
    </tr>
    @endforeach
</table>

{{ $pizzas->links() }}

<a href="{{ route('admin.home') }}"><button type="button">← Tableau de bord</button></a>

@endsection
