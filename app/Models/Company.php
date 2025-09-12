<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    /**
     * Get all of the drivers for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get all of the vehicles for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all of the trips for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
