@switch($status)
    @case('pending')    <span class="badge badge-pending">En attente</span> @break
    @case('preparing')  <span class="badge badge-preparing">En préparation</span> @break
    @case('delivering') <span class="badge badge-delivering">En livraison</span> @break
    @case('delivered')  <span class="badge badge-delivered">✓ Livrée</span> @break
@endswitch
