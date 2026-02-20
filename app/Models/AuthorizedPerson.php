<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorizedPerson extends Model
{
    protected $guarded = [];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'authorized_person_student');
    }

    public function getFormattedCpfAttribute()
    {
        if (!$this->cpf) return '';
        
        $cpf = preg_replace('/\D/', '', $this->cpf);
        if (strlen($cpf) !== 11) return $this->cpf;
        
        return substr($cpf, 0, 3) . '.' . 
               substr($cpf, 3, 3) . '.' . 
               substr($cpf, 6, 3) . '-' . 
               substr($cpf, 9, 2);
    }
}
