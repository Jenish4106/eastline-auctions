<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Machinery;
use App\Models\User;

class Order extends Model
{
    protected $fillable = [
        'order_id',
        'machinery_id',
        'user_id',
        'price',
        'purchase_date',
        'delivery_status',
        'process_date',
        'shipped_date',
        'in_transit_date',
        'delivered_date',
        'cancelled_date',
        'invoice_path',
        'first_name',
        'last_name',
        'phone_number',
        'vat_number',
        'billing_company',
        'billing_street',
        'billing_city',
        'billing_state',
        'billing_zip',
        'billing_country',
        'shipping_same_as_billing',
        'shipping_street',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
    ];
    
    protected $casts = [
        'purchase_date' => 'date',
        'process_date' => 'datetime',
        'shipped_date' => 'datetime',
        'in_transit_date' => 'datetime',
        'delivered_date' => 'datetime',
        'cancelled_date' => 'datetime',
        'delivery_status' => 'integer',
    ];
    
    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function getDeliveryStatusTextAttribute()
    {
        $statusMap = [
            0 => 'Process',
            1 => 'Shipped',
            2 => 'In Transit',
            3 => 'Delivered',
            4 => 'Cancelled',
        ];
        
        return $statusMap[$this->delivery_status] ?? 'Unknown';
    }
}