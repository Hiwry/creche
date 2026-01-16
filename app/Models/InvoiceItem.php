<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Item types.
     */
    public const TYPES = [
        'monthly_fee' => 'Mensalidade',
        'material_fee' => 'Taxa de Material',
        'extra_hours' => 'Horas Extras',
        'discount' => 'Desconto',
        'other' => 'Outro',
    ];

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->unit_price, 2, ',', '.');
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Recalculate total before saving
        static::saving(function (InvoiceItem $item) {
            $item->total = $item->quantity * $item->unit_price;
        });
        
        // Recalculate invoice totals after changes
        static::saved(function (InvoiceItem $item) {
            $item->invoice->recalculateTotals();
        });
        
        static::deleted(function (InvoiceItem $item) {
            $item->invoice->recalculateTotals();
        });
    }
}
