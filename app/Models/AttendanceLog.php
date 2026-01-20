<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AttendanceLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'date',
        'check_in',
        'check_out',
        'expected_start',
        'expected_end',
        'extra_minutes',
        'extra_charge',
        'picked_up_by',
        'notes',
        'registered_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
        'expected_start' => 'datetime:H:i',
        'expected_end' => 'datetime:H:i',
        'extra_minutes' => 'integer',
        'extra_charge' => 'decimal:2',
    ];

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the class.
     */
    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the user who registered this log.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Calculate extra minutes based on arrival and departure.
     */
    public function calculateExtraMinutes(int $toleranceMinutes = 10): int
    {
        $extraMinutes = 0;
        
        // Ensure we have strings for parsing
        $dateStr = $this->date instanceof Carbon ? $this->date->format('Y-m-d') : $this->date;
        
        // Check early arrival
        if ($this->check_in && $this->expected_start) {
            $checkInStr = $this->check_in instanceof Carbon ? $this->check_in->format('H:i') : $this->check_in;
            $expectedStartStr = $this->expected_start instanceof Carbon ? $this->expected_start->format('H:i') : $this->expected_start;
            
            $checkIn = Carbon::parse($dateStr . ' ' . $checkInStr);
            $expectedStart = Carbon::parse($dateStr . ' ' . $expectedStartStr);
            
            if ($checkIn->lt($expectedStart)) {
                $earlyMinutes = $checkIn->diffInMinutes($expectedStart);
                if ($earlyMinutes > $toleranceMinutes) {
                    $extraMinutes += $earlyMinutes - $toleranceMinutes;
                }
            }
        }
        
        // Check late departure
        if ($this->check_out && $this->expected_end) {
            $checkInStr = $this->check_in instanceof Carbon ? $this->check_in->format('H:i') : $this->check_in;
            $checkOutStr = $this->check_out instanceof Carbon ? $this->check_out->format('H:i') : $this->check_out;
            $expectedEndStr = $this->expected_end instanceof Carbon ? $this->expected_end->format('H:i') : $this->expected_end;
            
            $checkIn = $this->check_in ? Carbon::parse($dateStr . ' ' . $checkInStr) : null;
            $checkOut = Carbon::parse($dateStr . ' ' . $checkOutStr);
            $expectedEnd = Carbon::parse($dateStr . ' ' . $expectedEndStr);
            
            if ($checkOut->gt($expectedEnd)) {
                // If arrival was after the class ended, extra time only starts from arrival
                $startPoint = ($checkIn && $checkIn->gt($expectedEnd)) ? $checkIn : $expectedEnd;
                $lateMinutes = $startPoint->diffInMinutes($checkOut);
                
                if ($lateMinutes > $toleranceMinutes) {
                    $extraMinutes += $lateMinutes - $toleranceMinutes;
                }
            }
        }
        
        return $extraMinutes;
    }

    /**
     * Calculate extra charge based on minutes and rate.
     */
    public function calculateExtraCharge(float $hourlyRate = 15.00, int $toleranceMinutes = 10): float
    {
        $extraMinutes = $this->calculateExtraMinutes($toleranceMinutes);
        return ($extraMinutes / 60) * $hourlyRate;
    }

    /**
     * Update extra time calculations.
     */
    public function updateExtraCalculations(float $hourlyRate = 15.00, int $toleranceMinutes = 10): void
    {
        $this->extra_minutes = $this->calculateExtraMinutes($toleranceMinutes);
        $this->extra_charge = $this->calculateExtraCharge($hourlyRate, $toleranceMinutes);
        $this->save();
    }

    /**
     * Get formatted check-in time.
     */
    public function getFormattedCheckInAttribute(): string
    {
        return $this->check_in ? Carbon::parse($this->check_in)->format('H:i') : '-';
    }

    /**
     * Get formatted check-out time.
     */
    public function getFormattedCheckOutAttribute(): string
    {
        return $this->check_out ? Carbon::parse($this->check_out)->format('H:i') : '-';
    }

    /**
     * Get formatted extra time.
     */
    public function getFormattedExtraTimeAttribute(): string
    {
        if ($this->extra_minutes <= 0) return '-';
        
        $hours = floor($this->extra_minutes / 60);
        $minutes = $this->extra_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }
        
        return $minutes . 'min';
    }

    /**
     * Scope for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope for today.
     */
    public function scopeToday($query)
    {
        return $query->where('date', Carbon::today());
    }

    /**
     * Scope for a specific month.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }
}
