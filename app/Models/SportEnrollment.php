<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'student_id',
        'monthly_fee',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
