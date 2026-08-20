@extends('modele')

@section('content')

<h1>Tableau de bord</h1>

<div class="nav-actions">
    <form action="{{ route('logout') }}" method="post">
        @csrf
        <input type="submit" value="Se déconnecter">
    </form>
</div>

<h2>Statistiques</h2>
<div class="stats">
    <div class="stat-card">
        <div class="number">{{ $pizzaCount }}</div>
        <div class="label">Pizzas au menu</div>
    </div>
    <div class="stat-card">
        <div class="number">{{ $orderCount }}</div>
        <div class="label">Commandes totales</div>
    </div>
    <div class="stat-card">
        <div class="number">{{ $pendingCount }}</div>
        <div class="label">Commandes en cours</div>
    </div>
    <div class="stat-card">
        <div class="number">{{ $deliveredCount }}</div>
        <div class="label">Livrées</div>
    </div>
    <div class="stat-card">
        <div class="number">{{ $driverCount }}</div>
        <div class="label">Livreurs</div>
    </div>
</div>

<h2>Gestion</h2>
<ul style="list-style:none; display:flex; gap:12px; flex-wrap:wrap;">
    <li><a href="{{ route('admin.pizza.index') }}"><button type="button">🍕 Pizzas</button></a></li>
    <li><a href="{{ route('admin.order.index') }}"><button type="button">📋 Commandes</button></a></li>
    <li><a href="{{ route('admin.driver.index') }}"><button type="button">🚗 Livreurs</button></a></li>
</ul>

@endsection
