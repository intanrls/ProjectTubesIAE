<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expedition extends Model
{
    protected $fillable = [
        'order_id',
        'customer_name',
        'customer_address',
        'courier_name',
        'tracking_number',
        'shipping_status',
        'shipped_at',
        'delivered_at',
        'receipt_number',
    ];
}