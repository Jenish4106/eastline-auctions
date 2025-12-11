<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineryImage extends Model
{
    use HasFactory;

    protected $table = 'machinery_images';

    protected $fillable = [
        'machinery_id',
        'image_path',
        'sort_order'
    ];

    protected $casts = [
        'machinery_id' => 'integer',
        'sort_order' => 'integer'
    ];

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
}