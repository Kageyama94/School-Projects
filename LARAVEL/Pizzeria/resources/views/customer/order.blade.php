@extends('modele')

@section('content')

<h1>Commande de pizzas</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $erreur)
            <li>{{ $erreur }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('basket', Auth::id()) }}" method="post">
    @csrf

    Prénom : <input type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement"><br>
    Nom : <input type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement"><br>
    Email : <input type="email" name="email" value="{{ old('email', $customer?->email) }}"><br>
    Téléphone : <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}" pattern="[0-9\s\+\-\(\)]{6,20}" inputmode="tel" title="Numéro de téléphone valide" oninput="this.value=this.value.replace(/[^0-9\s\+\-\(\)]/g,'')"><br>
    Adresse : <input type="text" name="address" value="{{ old('address') }}"><br>
    Code postal : <input type="text" name="postal_code" value="{{ old('postal_code') }}" pattern="[0-9]{5}" inputmode="numeric" maxlength="5" title="5 chiffres" oninput="this.value=this.value.replace(/[^0-9]/g,'')"><br>

    <br>
    <strong>Quelle pizza désirez-vous ?</strong><br><br>

    <table border="1" cellpadding="5">
        <tr>
            <th>Nom</th>
            <th>Prix</th>
            <th>Description</th>
            <th>Quantité</th>
        </tr>
        @foreach($pizzas as $pizza)
        <tr>
            <td>{{ $pizza->name }}</td>
            <td>{{ number_format($pizza->price, 2) }} €</td>
            <td>{{ $pizza->description }}</td>
            <td style="white-space:nowrap">
                <button type="button" onclick="change({{ $pizza->id }}, -1)">−</button>
                <span id="qty-{{ $pizza->id }}">{{ old('pizza.'.$pizza->id, 0) }}</span>
                <button type="button" onclick="change({{ $pizza->id }}, 1)">+</button>
                <input type="hidden" name="pizza[{{ $pizza->id }}]" id="input-{{ $pizza->id }}"
                       value="{{ old('pizza.'.$pizza->id, 0) }}"
                       data-price="{{ $pizza->price }}">
            </td>
        </tr>
        @endforeach
    </table>

    <br>
    <strong>Total : <span id="total">0.00</span> €</strong>

    <br><br>
    <input type="submit" value="Valider">
</form>

<br>
<a href="{{ route('customer.home', Auth::id()) }}">← Retour à l'accueil</a>

<script>
function change(id, delta) {
    const input = document.getElementById('input-' + id);
    const span  = document.getElementById('qty-' + id);
    const val   = Math.max(0, parseInt(input.value) + delta);
    input.value = val;
    span.textContent = val;
    updateTotal();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('input[data-price]').forEach(input => {
        total += parseInt(input.value) * parseFloat(input.dataset.price);
    });
    document.getElementById('total').textContent = total.toFixed(2);
}

updateTotal();
</script>

@endsection
