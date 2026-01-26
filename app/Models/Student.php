<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guardian_id',
        'name',
        'birth_date',
        'gender',
        'photo',
        'observations',
        'status',
        'authorized_pickups',
        'monthly_fee',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'authorized_pickups' => 'array',
        'monthly_fee' => 'decimal:2',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the guardian of this student.
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * Get the health information.
     */
    public function health(): HasOne
    {
        return $this->hasOne(StudentHealth::class);
    }

    /**
     * Get the documents.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Get the enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get active enrollments.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->where('status', 'active');
    }

    /**
     * Get current classes through enrollments.
     */
    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'enrollments', 'student_id', 'class_id')
                    ->wherePivot('status', 'active')
                    ->withPivot(['status', 'start_date', 'end_date']);
    }

    /**
     * Get the monthly fees.
     */
    public function monthlyFees(): HasMany
    {
        return $this->hasMany(MonthlyFee::class);
    }

    /**
     * Get the material fees.
     */
    public function materialFees(): HasMany
    {
        return $this->hasMany(MaterialFee::class);
    }

    /**
     * Get the payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the attendance logs.
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get the invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the school materials.
     */
    public function studentMaterials(): HasMany
    {
        return $this->hasMany(StudentMaterial::class);
    }

    /**
     * Get the student's age.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) return null;
        return $this->birth_date->age;
    }

    /**
     * Get pending monthly fees count.
     */
    public function getPendingFeesCountAttribute(): int
    {
        return $this->monthlyFees()
                    ->whereIn('status', ['pending', 'overdue'])
                    ->count();
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'suspended' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get the photo URL.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (!$this->photo) {
            return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&background=7C3AED&color=fff";
        }

        // Handle case where path is just filename or full path
        $path = str_contains($this->photo, '/') ? $this->photo : 'students/photos/' . $this->photo;
        
        return asset('storage/' . $path);
    }

    /**
     * Get the status label in Portuguese.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'suspended' => 'Suspenso',
            default => $this->status,
        };
    }

    /**
     * Scope for active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for students with pending fees.
     */
    public function scopeWithPendingFees($query)
    {
        return $query->whereHas('monthlyFees', function ($q) {
            $q->whereIn('status', ['pending', 'overdue']);
        });
    }
}
