<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    const LICENSE_PENDING = 0;
    const LICENSE_APPROVED = 1;
    const LICENSE_DECLINED = 2;

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
        'is_license',
    ];
    
    protected $appends = [
        'license_status_text',
        'license_status_badge_class'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'integer',
            'is_license' => 'integer',
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
    
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function getStatusTextAttribute()
    {
        return $this->status == 1 ? 'Active' : 'Blocked';
    }
    
    public function getStatusBadgeClassAttribute()
    {
        return $this->status == 1 ? 'bg-success' : 'bg-danger';
    }
    
    public function getLicenseStatusTextAttribute()
    {
        switch ($this->is_license) {
            case self::LICENSE_PENDING:
                return 'Pending';
            case self::LICENSE_APPROVED:
                return 'Approved';
            case self::LICENSE_DECLINED:
                return 'Declined';
            default:
                return 'Unknown';
        }
    }
    
    public function getLicenseStatusBadgeClassAttribute()
    {
        switch ($this->is_license) {
            case self::LICENSE_PENDING:
                return 'bg-warning';
            case self::LICENSE_APPROVED:
                return 'bg-success';
            case self::LICENSE_DECLINED:
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }
    
    public function license()
    {
        return $this->hasOne(License::class);
    }
}