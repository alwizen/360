<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pretrip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'truck_id',
        'driver_id',
        'trip_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the truck for this pretrip
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    /**
     * Get the driver for this pretrip
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get all taps for this pretrip
     */
    public function taps(): HasMany
    {
        return $this->hasMany(PretripTap::class)->orderBy('tap_sequence');
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        $required = $this->truck->getRequiredPointsCount();
        $tapped = $this->taps()->count();

        if ($required === 0) {
            return 0;
        }

        return round(($tapped / $required) * 100, 2);
    }

    /**
     * Check if pretrip is complete
     */
    public function isComplete(): bool
    {
        $required = $this->truck->getRequiredPointsCount();
        $tapped = $this->taps()->count();

        return $tapped >= $required;
    }

    /**
     * Get remaining points to tap
     */
    public function getRemainingPointsAttribute(): int
    {
        $required = $this->truck->getRequiredPointsCount();
        $tapped = $this->taps()->count();

        return max(0, $required - $tapped);
    }

    /**
     * Auto-update status based on completion
     */
    public function updateStatus(): void
    {
        if ($this->isComplete()) {
            $this->update([
                'status' => 'completed',
                'end_time' => $this->end_time ?? now(),
            ]);
        } else {
            $this->update([
                'status' => 'in_progress',
            ]);
        }
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeIncomplete($query)
    {
        return $query->where('status', 'incomplete');
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeToday($query)
    {
        return $query->whereDate('trip_date', today());
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('trip_date', $date);
    }
}
