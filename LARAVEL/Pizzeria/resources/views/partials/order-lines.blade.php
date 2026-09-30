<table style="box-shadow:none; margin:0 0 20px 0">
    <tr>
        <th>Pizza</th>
        <th>Prix unit.</th>
        <th>Qté</th>
        <th>Sous-total</th>
    </tr>
    @foreach($orders as $order)
    <tr>
        <td>{{ $order->pizza_name }}</td>
        <td>{{ number_format($order->unit_price, 2, ',', ' ') }} €</td>
        <td>{{ $order->quantity }}</td>
        <td>{{ number_format($order->line_total, 2, ',', ' ') }} €</td>
    </tr>
    @endforeach
    <tr>
        <td colspan="3" style="text-align:right; font-weight:600">Total</td>
        <td><strong>{{ number_format($total, 2, ',', ' ') }} €</strong></td>
    </tr>
</table>
