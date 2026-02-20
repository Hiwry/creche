<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'material_id',
        'received',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'received' => 'boolean',
        'received_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(SchoolMaterial::class, 'material_id');
    }
}
