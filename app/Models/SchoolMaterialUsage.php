<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolMaterialUsage extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'student_id',
        'school_material_id',
        'quantity',
        'value',
        'usage_date',
        'invoice_id',
        'notes',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'value' => 'decimal:2',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function material(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SchoolMaterial::class, 'school_material_id');
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopePending($query)
    {
        return $query->whereNull('invoice_id');
    }
}
