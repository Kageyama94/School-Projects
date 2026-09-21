<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_group_id', 'pizza_name', 'unit_price', 'quantity', 'address', 'postal_code', 'status', 'driver_id', 'driver_name'];

    protected $casts = ['delivered_at' => 'datetime'];

    public function getLineTotalAttribute() {
        return $this->unit_price * $this->quantity;
    }

    public function customers() {
        return $this->belongsToMany(Customer::class);
    }

    public function driver() {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Recharge, pour une page de order_group_id paginés, toutes les commandes de chaque
     * groupe (une page ne contient qu'un agrégat par groupe, pas ses lignes complètes).
     */
    public static function groupsForPage($page, array $with = []) {
        $orders = static::with($with)
            ->whereIn('order_group_id', $page->pluck('order_group_id'))
            ->get()
            ->groupBy('order_group_id');

        return $page->pluck('order_group_id')
            ->mapWithKeys(fn($groupId) => [$groupId => $orders->get($groupId, collect())]);
    }
}
