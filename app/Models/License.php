<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_DECLINED = 2;

    protected $fillable = [
        'user_id',
        'file',
        'status',
    ];

    protected $appends = ['file_url'];

    protected $visible = [
        'id',
        'user_id',
        'file',
        'status',
        'file_url'
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status'  => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute()
    {
        return $this->file ? url($this->file) : null;
    }
}
