<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'monthly_fee',
        'is_active',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(SportSchedule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(SportEnrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'sport_enrollments')
            ->withPivot(['id', 'monthly_fee', 'start_date', 'end_date', 'status'])
            ->withTimestamps();
    }

    public function getFormattedMonthlyFeeAttribute(): string
    {
        return 'R$ ' . number_format($this->monthly_fee, 2, ',', '.');
    }
}
