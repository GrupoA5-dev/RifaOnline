<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public static function value(string $group, string $key, mixed $default = null): mixed
    {
        return static::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?? $default;
    }
}
