<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHealth extends Model
{
    use HasFactory;

    protected $table = 'student_health';

    protected $fillable = [
        'student_id',
        'health_plan_name',
        'health_plan_number',
        'health_plan_validity',
        'medications',
        'medication_schedule',
        'allergies',
        'dietary_restrictions',
        'medical_conditions',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'health_plan_validity' => 'date',
    ];

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if health plan is valid.
     */
    public function getIsHealthPlanValidAttribute(): bool
    {
        if (!$this->health_plan_validity) return false;
        return $this->health_plan_validity->isFuture();
    }

    /**
     * Check if student has allergies.
     */
    public function getHasAllergiesAttribute(): bool
    {
        return !empty($this->allergies);
    }

    /**
     * Check if student takes medications.
     */
    public function getHasMedicationsAttribute(): bool
    {
        return !empty($this->medications);
    }
}
