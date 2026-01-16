<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaterialFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'year',
        'amount',
        'discount',
        'amount_paid',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get payments for this material fee.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
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
            'cancelled' => 'secondary',
            default => 'secondary',
        };
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
        return $query->whereIn('status', ['pending', 'partial']);
    }

    /**
     * Scope for a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
