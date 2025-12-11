<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    use HasFactory;

    protected $table = 'machinery';

    protected $fillable = [
        'name',
        'category_id',
        'year',
        'weight',
        'working_hours',
        'condition',
        'fuel',
        'buy_now_price',
        'bid_start_price',
        'bid_end_time',
        'description',
        'specification',
        'offer',
        'status'
    ];

    protected $casts = [
        'category_id' => 'integer',
        'specification' => 'array',
        'offer' => 'array',
        'status' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function images()
    {
        return $this->hasMany(MachineryImage::class);
    }

    public function getCreatedDateAttribute()
    {
        return $this->created_at->format('F d, Y');
    }

    public function getUpdatedDateAttribute()
    {
        return $this->updated_at->format('F d, Y');
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 1:
                return '<span class="badge bg-label-success">Active</span>';
            case 2:
                return '<span class="badge bg-label-danger">Sold</span>';
            case 3:
                return '<span class="badge bg-label-warning">Closed</span>';
            default:
                return '<span class="badge bg-label-secondary">Unknown</span>';
        }
    }
}