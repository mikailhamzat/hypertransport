<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory;

    /**
     * Get all of the trips for the Vehicle
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Scope a query to only include vehicles available between the given times.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @param  \Illuminate\Support\Carbon  $start
     * @param  \Illuminate\Support\Carbon  $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailableBetween($q, $start, $end)
    {
        return $q->whereDoesntHave('trips', fn($t) => $t->overlapping($start, $end));
    }

    /**
     * Get the company that owns the Vehicle
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
