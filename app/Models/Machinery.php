<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    use HasFactory;

    protected $table = 'machinery';

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
        'bid_end_time',
        'description',
        'specification',
        'offer',
        'status'
    ];

    protected $casts = [
        'specification' => 'array',
        'buy_now_price' => 'decimal:2',
        'bid_start_price' => 'decimal:2',
        'bid_end_time' => 'datetime'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(MachineryFileManager::class);
    }
}