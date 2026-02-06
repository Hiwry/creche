<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Role constants.
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ATTENDANT = 'attendant';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_FINANCIAL = 'financial';

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_TEACHER => 'Professor',
    ];

    /**
     * Get classes taught by this user (if teacher).
     */
    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'teacher_id');
    }

    /**
     * Get payments received by this user.
     */
    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    /**
     * Get attendance logs registered by this user.
     */
    public function attendanceLogsRegistered(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'registered_by');
    }

    /**
     * Get the role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is attendant.
     */
    public function isAttendant(): bool
    {
        return $this->role === self::ROLE_ATTENDANT;
    }

    /**
     * Check if user is teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    /**
     * Check if user is financial.
     */
    public function isFinancial(): bool
    {
        return $this->role === self::ROLE_FINANCIAL;
    }

    /**
     * Check if user can manage students.
     */
    public function canManageStudents(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ATTENDANT]);
    }

    /**
     * Check if user can manage financial.
     */
    public function canManageFinancial(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCIAL, self::ROLE_ATTENDANT]);
    }

    /**
     * Check if user can access invoices list/actions.
     */
    public function canAccessInvoices(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCIAL, self::ROLE_ATTENDANT, self::ROLE_TEACHER]);
    }

    /**
     * Check if user can view invoice values.
     */
    public function canViewInvoiceValues(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCIAL, self::ROLE_ATTENDANT]);
    }

    /**
     * Check if user can send invoices/receipts.
     */
    public function canSendInvoices(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCIAL, self::ROLE_ATTENDANT, self::ROLE_TEACHER]);
    }

    /**
     * Check if user can download invoice PDF.
     */
    public function canDownloadInvoicePdf(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCIAL, self::ROLE_ATTENDANT, self::ROLE_TEACHER]);
    }

    /**
     * Check if user can mark invoices as paid.
     */
    public function canMarkInvoicePaid(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCIAL, self::ROLE_ATTENDANT, self::ROLE_TEACHER]);
    }

    /**
     * Check if user can manage settings.
     */
    public function canManageSettings(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user can register attendance.
     */
    public function canRegisterAttendance(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ATTENDANT, self::ROLE_TEACHER]);
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Generate initials avatar
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=7C3AED&color=fff';
    }
}
