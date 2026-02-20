<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, $value): void
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting) {
            $setting->value = is_array($value) ? json_encode($value) : $value;
            $setting->save();
        } else {
            self::create([
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : $value,
            ]);
        }

        Cache::forget("setting.{$key}");
    }

    /**
     * Cast value based on type.
     */
    private static function castValue($value, string $type)
    {
        return match($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get all settings by group.
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => self::castValue($setting->value, $setting->type)];
        })->toArray();
    }

    /**
     * Helper methods for common settings.
     */
    public static function getCompanyName(): string
    {
        return self::getValue('company_name', 'SchoolHub');
    }

    public static function getDefaultMonthlyFee(): float
    {
        return (float) self::getValue('default_monthly_fee', 500.00);
    }

    public static function getDefaultMaterialFee(): float
    {
        return (float) self::getValue('default_material_fee', 300.00);
    }

    public static function getExtraHourRate(): float
    {
        return (float) self::getValue('extra_hour_rate', 15.00);
    }

    public static function getExtraHourTolerance(): int
    {
        return (int) self::getValue('extra_hour_tolerance', 10);
    }

    public static function getPaymentDueDay(): int
    {
        return (int) self::getValue('payment_due_day', 10);
    }
}
