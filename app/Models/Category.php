<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'category_name',
        'total_machinery'
    ];

    protected $casts = [
        'total_machinery' => 'integer'
    ];

    public function getCreatedDateAttribute()
    {
        return $this->created_at->format('F d, Y');
    }
    
    public function getUpdatedDateAttribute()
    {
        return $this->updated_at->format('F d, Y');
    }
}