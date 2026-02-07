<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RfidPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'truck_id',
        'rfid_code',
        'location',
        'point_number',
        'is_active',
    ];

    protected $casts = [
        'point_number' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the truck that owns this RFID point
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    /**
     * Get all pretrip taps for this RFID point
     */
    public function pretripTaps(): HasMany
    {
        return $this->hasMany(PretripTap::class);
    }

    /**
     * Scope untuk filter RFID point yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope untuk mencari berdasarkan RFID code
     */
    public function scopeByRfidCode($query, string $rfidCode)
    {
        return $query->where('rfid_code', $rfidCode);
    }

    /**
     * Get formatted location name
     */
    public function getFormattedLocationAttribute(): string
    {
        return "Point {$this->point_number}: {$this->location}";
    }
}
