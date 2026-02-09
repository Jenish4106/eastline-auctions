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
        'shipping_cost',
        'purchase_date',
        'delivery_status',
        'process_date',
        'shipped_date',
        'in_transit_date',
        'delivered_date',
        'cancelled_date',
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
        'payment_slip_path',
        'payment_slip_status',
    ];
    
    protected $appends = [
        'invoice_url',
        'contract_url',
        'payment_slip_url',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'process_date' => 'datetime',
        'shipped_date' => 'datetime',
        'in_transit_date' => 'datetime',
        'delivered_date' => 'datetime',
        'cancelled_date' => 'datetime',
        'delivery_status' => 'integer',
        'payment_slip_status' => 'integer',
    ];
    
    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->hasOne(MachineryFileManager::class, 'order_id', 'id')->where('type', 'invoice');
    }

    public function contract()
    {
        return $this->hasOne(MachineryFileManager::class, 'order_id', 'id')->where('type', 'contract_pdf');
    }

    public function getInvoiceUrlAttribute()
    {
        $invoice = $this->invoice;
        return $invoice ? asset($invoice->image_path) : null;
    }

    public function getContractUrlAttribute()
    {
        $contract = $this->contract;
        return $contract ? asset($contract->image_path) : null;
    }

    public function getPaymentSlipUrlAttribute()
    {
        return $this->payment_slip_path ? asset($this->payment_slip_path) : null;
    }
    
    public function getPaymentSlipStatusTextAttribute()
    {
        $statusMap = [
            0 => 'Pending',
            1 => 'Approve',
            2 => 'Decline',
        ];
        
        return $statusMap[$this->payment_slip_status] ?? 'Unknown';
    }

    public function getDeliveryStatusTextAttribute()
    {
        $statusMap = [
            0 => 'Pending',
            1 => 'Confirmed',
            2 => 'Process',
            3 => 'Shipped',
            4 => 'In Transit',
            5 => 'Delivered',
            6 => 'Cancelled',
        ];
        
        return $statusMap[$this->delivery_status] ?? 'Unknown';
    }
}