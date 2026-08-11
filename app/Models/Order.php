<?php

namespace App\Models;

use App\Models\Machinery;
use App\Models\OrderTracking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where('is_deleted', 0);
        });
    }

    protected $fillable = [
        'order_id',
        'machinery_id',
        'user_id',
        'type',
        'price',
        'shipping_cost',
        'purchase_date',
        'sales_agreement_date',
        'awaiting_invoice_date',
        'settle_payment_date',
        'confirmation_date',
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
        'is_deleted',
        'is_regenerated',
    ];

    protected $appends = [
        'invoice_url',
        'contract_url',
        'payment_slip_url',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'sales_agreement_date' => 'datetime',
        'awaiting_invoice_date' => 'datetime',
        'settle_payment_date' => 'datetime',
        'confirmation_date' => 'datetime',
        'process_date' => 'datetime',
        'shipped_date' => 'datetime',
        'in_transit_date' => 'datetime',
        'delivered_date' => 'datetime',
        'cancelled_date' => 'datetime',
        'delivery_status' => 'integer',
        'payment_slip_status' => 'integer',
        'is_deleted' => 'integer',
        'is_regenerated' => 'boolean',
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

    public function trackingEntries()
    {
        return $this->hasMany(OrderTracking::class, 'order_id', 'id')->orderBy('tracking_date', 'asc');
    }

    public function getInvoiceUrlAttribute()
    {
        $invoice = MachineryFileManager::where('order_id', $this->id)
            ->where('type', 'invoice')
            ->first();

        if ($invoice) {
            $path = public_path($invoice->image_path);
            if (file_exists($path)) {
                return asset('public/' . ltrim($invoice->image_path, '/')) . '?t=' . filemtime($path);
            }
            return asset('public/' . ltrim($invoice->image_path, '/'));
        }

        return null;
    }

    public function getContractUrlAttribute()
    {
        $contract = MachineryFileManager::where('order_id', $this->id)
            ->where('type', 'contract')
            ->first();

        return $contract ? asset('public/' . ltrim($contract->image_path, '/')) : null;
    }

    public function getPaymentSlipUrlAttribute()
    {
        return $this->payment_slip_path ? asset('public/' . ltrim($this->payment_slip_path, '/')) : null;
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
            0 => 'Order Submitted',
            1 => 'Sales Agreement',
            2 => 'Awaiting Invoice',
            3 => 'Settle Payment',
            4 => 'Payment Confirmed',
            5 => 'Processing',
            6 => 'Shipping Started',
            7 => 'In Transit',
            8 => 'Delivered',
            9 => 'Cancelled',
        ];

        return $statusMap[$this->delivery_status] ?? 'Unknown';
    }
}
