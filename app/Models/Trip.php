<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
    /** @use HasFactory<\Database\Factories\TripFactory> */
    use HasFactory;

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the status of the trip.
     *
     * @return \App\Enums\Status
     */
    public function getStatusAttribute(): Status
    {
        if ($this->cancelled_at) {
            return Status::CANCELLED;
        }

        if ($this->completed_at) {
            return Status::COMPLETED;
        }

        $now = now();

        if ($this->starts_at > $now) {
            return Status::SCHEDULED;
        }

        if ($this->ends_at <= $now) {
            return Status::COMPLETED;
        }

        return Status::ACTIVE;
    }


    /**
     * Get the company that owns the Trip
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the driver that owns the Trip
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle that owns the Trip
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Scope a query to only include overlapping trips.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @param  \Illuminate\Support\Carbon  $start
     * @param  \Illuminate\Support\Carbon  $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverlapping($q, $start, $end)
    {
        return $q->where(
            fn($q) =>
            $q->where('starts_at', '<', $end)
                ->where('ends_at',   '>', $start)
        );
    }

    /**
     * Scope a query to only include ongoing trips.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOngoing($q)
    {
        return $q->where('starts_at', '<=', now())->where('ends_at', '>=', now())
            ->whereNull('completed_at')
            ->whereNull('cancelled_at');
    }
}
