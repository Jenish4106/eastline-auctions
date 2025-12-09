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

    // Accessor to get formatted created date
    public function getCreatedDateAttribute()
    {
        return $this->created_at->format('F d, Y');
    }
}