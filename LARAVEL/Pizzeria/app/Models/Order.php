<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['pizza_name', 'unit_price', 'quantity', 'address', 'postal_code', 'status', 'driver_id'];

    public function customers() {
        return $this->belongsToMany(Customer::class);
    }

    public function driver() {
        return $this->belongsTo(Driver::class);
    }
}
