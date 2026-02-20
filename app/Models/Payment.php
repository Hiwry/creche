<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payable_type',
        'payable_id',
        'amount',
        'method',
        'payment_date',
        'receipt_path',
        'transaction_id',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Payment methods.
     */
    public const METHODS = [
        'cash' => 'Dinheiro',
        'pix' => 'PIX',
        'credit_card' => 'Cartão de Crédito',
        'debit_card' => 'Cartão de Débito',
        'bank_transfer' => 'Transferência Bancária',
        'other' => 'Outro',
    ];

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the payable model (MonthlyFee, MaterialFee, etc.).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who received this payment.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the method label.
     */
    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    /**
     * Scope for a specific date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('payment_date', [$start, $end]);
    }

    /**
     * Scope for a specific method.
     */
    public function scopeMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            // Update the payable status
            if ($payment->payable) {
                $payment->payable->amount_paid += $payment->amount;
                $payment->payable->updateStatus();
            }
        });
    }
}
