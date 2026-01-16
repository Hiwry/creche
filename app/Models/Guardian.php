<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cpf',
        'rg',
        'phone',
        'whatsapp',
        'email',
        'cep',
        'address',
        'address_number',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'notes',
    ];

    /**
     * Get the students for this guardian.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_number,
            $this->address_complement,
            $this->neighborhood,
            $this->city,
            $this->state,
            $this->cep,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Format CPF for display.
     */
    public function getFormattedCpfAttribute(): string
    {
        if (!$this->cpf) return '';
        
        $cpf = preg_replace('/\D/', '', $this->cpf);
        if (strlen($cpf) !== 11) return $this->cpf;
        
        return substr($cpf, 0, 3) . '.' . 
               substr($cpf, 3, 3) . '.' . 
               substr($cpf, 6, 3) . '-' . 
               substr($cpf, 9, 2);
    }

    /**
     * Format phone for display.
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (!$this->phone) return '';
        
        $phone = preg_replace('/\D/', '', $this->phone);
        
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 5) . '-' . 
                   substr($phone, 7, 4);
        }
        
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 4) . '-' . 
                   substr($phone, 6, 4);
        }
        
        return $this->phone;
    }
}
