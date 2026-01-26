<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MonthlyFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'year',
        'month',
        'amount',
        'discount',
        'amount_paid',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Month names in Portuguese.
     */
    public const MONTHS = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
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
     * Get payments for this monthly fee.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Get the month name.
     */
    public function getMonthNameAttribute(): string
    {
        return self::MONTHS[$this->month] ?? '';
    }

    /**
     * Get reference (Mês/Ano).
     */
    public function getReferenceAttribute(): string
    {
        return $this->month_name . '/' . $this->year;
    }

    /**
     * Get the net amount (amount - discount).
     */
    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this->discount;
    }

    /**
     * Get the remaining amount.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->net_amount - $this->amount_paid);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'partial' => 'Parcial',
            'paid' => 'Pago',
            'overdue' => 'Atrasado',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'partial' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Check if overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'paid') return false;
        if (!$this->due_date) return false;
        
        return $this->due_date->isPast();
    }

    /**
     * Update status based on payments.
     */
    public function updateStatus(): void
    {
        if ($this->amount_paid >= $this->net_amount) {
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        } elseif ($this->is_overdue) {
            $this->status = 'overdue';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }

    /**
     * Scope for pending fees.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partial', 'overdue']);
    }

    /**
     * Scope for paid fees.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for a specific month.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }
}
