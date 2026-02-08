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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            \Log::info('PretripTap Creating', $model->toArray());
        });

        static::created(function ($model) {
            \Log::info('PretripTap Created', ['id' => $model->id]);
        });
    }

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
