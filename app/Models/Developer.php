<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'developer_bio',
        'developer_website',
        'status',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the developer profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all applications created by this developer.
     */
    public function applications()
    {
        return $this->hasMany(DeveloperApplication::class, 'user_id', 'user_id');
    }

    /**
     * Check if developer account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if developer is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Activate the developer account.
     */
    public function activate()
    {
        $this->update([
            'status' => 'active',
            'verified_at' => now(),
        ]);
    }

    /**
     * Suspend the developer account.
     */
    public function suspend()
    {
        $this->update(['status' => 'suspended']);
    }
}
