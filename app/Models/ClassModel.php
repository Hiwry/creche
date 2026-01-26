<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'teacher_id',
        'name',
        'description',
        'days_of_week',
        'start_time',
        'end_time',
        'capacity',
        'monthly_fee',
        'status',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'monthly_fee' => 'decimal:2',
        'capacity' => 'integer',
    ];

    /**
     * Days of week labels.
     */
    public const DAYS_OF_WEEK = [
        'monday' => 'Segunda',
        'tuesday' => 'Terça',
        'wednesday' => 'Quarta',
        'thursday' => 'Quinta',
        'friday' => 'Sexta',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    /**
     * Get the teacher.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /**
     * Get active enrollments.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id')->where('status', 'active');
    }

    /**
     * Get students through enrollments.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
                    ->wherePivot('status', 'active')
                    ->withPivot(['status', 'start_date', 'end_date']);
    }

    /**
     * Get attendance logs.
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'class_id');
    }

    /**
     * Get days of week labels.
     */
    public function getDaysLabelsAttribute(): array
    {
        if (!$this->days_of_week) return [];
        
        return array_map(function ($day) {
            return self::DAYS_OF_WEEK[$day] ?? $day;
        }, $this->days_of_week);
    }

    /**
     * Get formatted days.
     */
    public function getFormattedDaysAttribute(): string
    {
        return implode(', ', $this->days_labels);
    }

    /**
     * Get formatted schedule.
     */
    public function getFormattedScheduleAttribute(): string
    {
        $start = $this->start_time ? $this->start_time->format('H:i') : '';
        $end = $this->end_time ? $this->end_time->format('H:i') : '';
        
        return $start . ' - ' . $end;
    }

    /**
     * Get the number of active students.
     */
    public function getActiveStudentsCountAttribute(): int
    {
        return $this->activeEnrollments()->count();
    }

    /**
     * Get available spots.
     */
    public function getAvailableSpotsAttribute(): ?int
    {
        if (!$this->capacity) return null;
        return max(0, $this->capacity - $this->active_students_count);
    }

    /**
     * Check if class is full.
     */
    public function getIsFullAttribute(): bool
    {
        if (!$this->capacity) return false;
        return $this->active_students_count >= $this->capacity;
    }

    /**
     * Scope for active classes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
