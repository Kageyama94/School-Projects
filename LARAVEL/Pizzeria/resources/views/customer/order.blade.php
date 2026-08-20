@extends('modele')

@section('content')

<h1>Commande de pizzas</h1>

@if ($errors->any())
    <div class="alert-error">
        <ul style="list-style:none">
            @foreach ($errors->all() as $erreur)
                <li>{{ $erreur }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('order.store', Auth::id()) }}" method="post">
    @csrf

    <div class="form-card" style="max-width:600px; margin-bottom:24px">
        <h2 style="margin-top:0">Vos coordonnées</h2>
        <div class="form-row">
            <label>Prénom</label>
            <input type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement">
        </div>
        <div class="form-row">
            <label>Nom</label>
            <input type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" pattern="[A-Za-zÀ-ÿ\s\-']+" title="Lettres uniquement">
        </div>
        <div class="form-row">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $customer?->email) }}">
        </div>
        <div class="form-row">
            <label>Téléphone</label>
            <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}" pattern="[0-9\s\+\-\(\)]{6,20}" inputmode="tel" title="Numéro de téléphone valide" oninput="this.value=this.value.replace(/[^0-9\s\+\-\(\)]/g,'')">
        </div>
        <div class="form-row">
            <label>Adresse</label>
            <input type="text" name="address" value="{{ old('address') }}">
        </div>
        <div class="form-row">
            <label>Code postal</label>
            <input type="text" name="postal_code" value="{{ old('postal_code') }}" pattern="[0-9]{5}" inputmode="numeric" maxlength="5" title="5 chiffres" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
    </div>

    <h2>Choisissez vos pizzas</h2>
    <table>
        <tr>
            <th>Nom</th>
            <th>Prix</th>
            <th>Description</th>
            <th>Quantité</th>
        </tr>
        @foreach($pizzas as $pizza)
        <tr>
            <td>{{ $pizza->name }}</td>
            <td>{{ number_format($pizza->price, 2, ',', ' ') }} €</td>
            <td>{{ $pizza->description }}</td>
            <td>
                <div class="qty-control">
                    <button type="button" onclick="change({{ $pizza->id }}, -1)">−</button>
                    <span id="qty-{{ $pizza->id }}">{{ old('pizza.'.$pizza->id, 0) }}</span>
                    <button type="button" onclick="change({{ $pizza->id }}, 1)">+</button>
                </div>
                <input type="hidden" name="pizza[{{ $pizza->id }}]" id="input-{{ $pizza->id }}"
                       value="{{ old('pizza.'.$pizza->id, 0) }}"
                       data-price="{{ $pizza->price }}">
            </td>
        </tr>
        @endforeach
    </table>

    <div style="margin:16px 0; font-size:1.1rem">
        <strong>Total : <span id="total">0.00</span> €</strong>
    </div>

    <div style="display:flex; gap:12px; align-items:center">
        <input type="submit" value="Valider la commande">
        <a href="{{ route('customer.home', Auth::id()) }}">← Retour</a>
    </div>
</form>

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
    document.getElementById('total').textContent = total.toFixed(2).replace('.', ',');
}
updateTotal();
</script>

@endsection
