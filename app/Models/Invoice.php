<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'year',
        'month',
        'subtotal',
        'discount',
        'total',
        'status',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
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
        return $this->belongsTo(Student::class)->withTrashed();
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
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
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Rascunho',
            'sent' => 'Enviada',
            'paid' => 'Paga',
            'overdue' => 'Em Atraso',
            'cancelled' => 'Cancelada',
            default => $this->status,
        };
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $this->total = $this->subtotal - $this->discount;
        $this->save();
    }

    /**
     * Add an item to the invoice.
     */
    public function addItem(string $type, string $description, float $quantity, float $unitPrice, ?string $notes = null): InvoiceItem
    {
        $item = $this->items()->create([
            'type' => $type,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
            'notes' => $notes,
        ]);
        
        $this->recalculateTotals();
        
        return $item;
    }

    /**
     * Generate invoice number.
     */
    public function getInvoiceNumberAttribute(): string
    {
        return str_pad($this->id, 6, '0', STR_PAD_LEFT) . '/' . $this->year;
    }

    /**
     * Scope for a specific month.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope for pending invoices.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'overdue']);
    }
}
