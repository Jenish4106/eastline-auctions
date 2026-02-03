<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    use HasFactory;

    protected $table = 'machinery';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($machinery) {
            if (empty($machinery->auction_id)) {
                $machinery->auction_id = static::generateUniqueAuctionId();
            }
        });
    }

    protected static function generateUniqueAuctionId()
    {
        do {
            $timestamp = date('His');
            $randomPart = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            $auctionId = substr($timestamp . rand(10, 99), 0, 6);
        } while (static::where('auction_id', $auctionId)->exists());

        return $auctionId;
    }

    protected $fillable = [
        'category_id',
        'make',
        'model',
        'year',
        'weight',
        'working_hours',
        'condition',
        'fuel',
        'serial_number',
        'buy_now_price',
        'bid_start_price',
        'bid_start_time',
        'bid_end_days',
        'bid_end_time',
        'bid_status',
        'won_user',
        'bid_won_date',
        'is_purchase',
        'contract_path',
        'contract_status',
        'description',
        'offer',
        'status',
        'auction_id'
    ];

    protected $casts = [
        'buy_now_price' => 'decimal:2',
        'bid_start_price' => 'decimal:2',
        'bid_start_time' => 'datetime',
        'bid_end_days' => 'integer',
        'bid_end_time' => 'datetime',
        'bid_status' => 'string',
        'contract_status' => 'integer',
        'won_user' => 'integer',
        'bid_won_date' => 'datetime',
        'is_purchase' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(MachineryFileManager::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function wonUser()
    {
        return $this->belongsTo(User::class, 'won_user');
    }
}
