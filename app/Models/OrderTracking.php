<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    protected $table = 'order_tracking';

    protected $fillable = [
        'order_id',
        'tracking_date',
        'city',
        'status',
    ];

    protected $casts = [
        'tracking_date' => 'datetime',
    ];

    /**
     * Relationship: belongs to an Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
