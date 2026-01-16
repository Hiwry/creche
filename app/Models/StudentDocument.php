<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'name',
        'filename',
        'path',
        'mime_type',
        'size',
        'notes',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Document types available.
     */
    public const TYPES = [
        'rg' => 'RG',
        'cpf' => 'CPF',
        'certidao_nascimento' => 'Certidão de Nascimento',
        'comprovante_residencia' => 'Comprovante de Residência',
        'receita_medica' => 'Receita Médica',
        'laudo_medico' => 'Laudo Médico',
        'carteira_vacinacao' => 'Carteira de Vacinação',
        'foto' => 'Foto',
        'declaracao' => 'Declaração',
        'contrato' => 'Contrato',
        'outro' => 'Outro',
    ];

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) return '';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if the document is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }

    /**
     * Check if the document is a PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
