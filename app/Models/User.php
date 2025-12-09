<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_no',
        'address',
        'company_name',
        'city',
        'state',
        'zip_code',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'integer',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    
    // Accessor to get full name
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    // Accessor to get status text
    public function getStatusTextAttribute()
    {
        return $this->status == 1 ? 'Active' : 'Blocked';
    }
    
    // Accessor to get status badge class
    public function getStatusBadgeClassAttribute()
    {
        return $this->status == 1 ? 'bg-success' : 'bg-danger';
    }
}