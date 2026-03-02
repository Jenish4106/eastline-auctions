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
        'applicant_id',
        'front_side',
        'back_side',
        'status',
        'is_sumsub',
    ];

    protected $appends = ['front_side_url', 'back_side_url'];

    protected $visible = [
        'id',
        'user_id',
        'applicant_id',
        'front_side',
        'back_side',
        'status',
        'is_sumsub',
        'front_side_url',
        'back_side_url'
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status'  => 'integer',
            'is_sumsub' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFrontSideUrlAttribute()
    {
        return $this->front_side ? url($this->front_side) : null;
    }

    public function getBackSideUrlAttribute()
    {
        return $this->back_side ? url($this->back_side) : null;
    }
}
