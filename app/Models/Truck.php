<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'truck_id',
        'capacity',
        'merk',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /**
     * Get all RFID points for this truck
     */
    public function rfidPoints(): HasMany
    {
        return $this->hasMany(RfidPoint::class);
    }

    /**
     * Get active RFID points only
     */
    public function activeRfidPoints(): HasMany
    {
        return $this->hasMany(RfidPoint::class)->where('is_active', true);
    }

    /**
     * Get the number of registered RFID points
     */
    public function getRegisteredPointsCountAttribute(): int
    {
        return $this->rfidPoints()->count();
    }

    /**
     * Check if all required points are registered
     * Based on capacity: 4,5 KL = 2 points, 8,16 KL = 3 points, 24,32 KL = 5 points
     */
    public function hasCompleteRfidPoints(): bool
    {
        $requiredPoints = $this->getRequiredPointsCount();
        return $this->activeRfidPoints()->count() >= $requiredPoints;
    }

    /**
     * Get required points count based on capacity
     */
    public function getRequiredPointsCount(): int
    {
        return match ((int)$this->capacity) {
            4, 5 => 2,
            8, 16 => 3,
            24, 32 => 5,
            default => 2,
        };
    }

    /**
     * Get all pretrips for this truck
     */
    public function pretrips(): HasMany
    {
        return $this->hasMany(Pretrip::class);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeAfkir($query)
    {
        return $query->where('status', 'afkir');
    }
}
