<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $table = 'site_settings';
    protected $primaryKey = 'setting_id';
    public $timestamps = false;

    protected $fillable = [
        'key_name',
        'key_value',
        'description',
        'is_deleted'
    ];

    protected $casts = [
        'updated_at' => 'datetime'
    ];

    // Scope để lấy chỉ các setting active
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    // Scope để lấy các setting inactive
    public function scopeInactive($query)
    {
        return $query->where('is_deleted', 'inactive');
    }

    // Tự động cập nhật updated_at khi save
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            $model->updated_at = now();
        });
    }

    // Helper method để lấy giá trị setting theo key
    public static function getValue($key, $default = null)
    {
        $setting = self::active()->where('key_name', $key)->first();
        return $setting ? $setting->key_value : $default;
    }

    // Helper method để set giá trị setting
    public static function setValue($key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['key_name' => $key],
            [
                'key_value' => $value,
                'description' => $description,
                'is_deleted' => 'active'
            ]
        );
    }
}