<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Expense extends Model
{
    protected $fillable = [
        'user_id',
        'description',
        'category',
        'amount',
        'expense_date',
        'payment_method',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Categories
    const CATEGORIES = [
        'material' => 'Material',
        'manutencao' => 'Manutenção',
        'pessoal' => 'Pessoal',
        'alimentacao' => 'Alimentação',
        'transporte' => 'Transporte',
        'outros' => 'Outros',
    ];

    // Payment Methods
    const PAYMENT_METHODS = [
        'cash' => 'Dinheiro',
        'pix' => 'PIX',
        'card' => 'Cartão',
        'transfer' => 'Transferência',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->expense_date->format('d/m/Y');
    }

    // Scopes
    public function scopeOfMonth($query, int $year, int $month)
    {
        return $query->whereYear('expense_date', $year)
                     ->whereMonth('expense_date', $month);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Static Methods
    public static function getMonthlyTotal(int $year = null, int $month = null): float
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;
        
        return self::ofMonth($year, $month)->sum('amount');
    }

    public static function getByCategory(int $year = null, int $month = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;
        
        $expenses = self::ofMonth($year, $month)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();
        
        $result = [];
        foreach (self::CATEGORIES as $key => $label) {
            $found = $expenses->firstWhere('category', $key);
            $result[$key] = [
                'label' => $label,
                'total' => $found ? (float) $found->total : 0,
            ];
        }
        
        return $result;
    }
}
