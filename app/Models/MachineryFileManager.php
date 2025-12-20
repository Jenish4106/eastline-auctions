<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineryFileManager extends Model
{
    use HasFactory;

    protected $table = 'machinery_files';

    protected $fillable = [
        'machinery_id',
        'image_path',
        'type'
    ];

    protected $casts = [
        'machinery_id' => 'integer'
    ];

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
}