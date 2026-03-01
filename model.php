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


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PretripTap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pretrip_id',
        'rfid_point_id',
        'tapped_at',
        'tap_sequence',
    ];

    protected $casts = [
        'tapped_at' => 'datetime',
        'tap_sequence' => 'integer',
    ];

    /**
     * Get the pretrip for this tap
     */
    public function pretrip(): BelongsTo
    {
        return $this->belongsTo(Pretrip::class);
    }

    /**
     * Get the RFID point that was tapped
     */
    public function rfidPoint(): BelongsTo
    {
        return $this->belongsTo(RfidPoint::class);
    }

    /**
     * Scope untuk urutan tap
     */
    public function scopeBySequence($query)
    {
        return $query->orderBy('tap_sequence');
    }

    /**
     * Get formatted tap info
     */
    public function getFormattedTapInfoAttribute(): string
    {
        return "Point {$this->rfidPoint->point_number} - {$this->rfidPoint->location} @ {$this->tapped_at->format('H:i:s')}";
    }
}


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


