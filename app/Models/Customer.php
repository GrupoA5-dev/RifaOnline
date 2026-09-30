<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'document_type',
        'document_number',
    ];

    protected $hidden = [
        'public_token',
        'access_pin',
    ];

    protected function casts(): array
    {
        return [
            'access_pin' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            $customer->uuid ??= (string) Str::uuid();
            $customer->public_token ??= Str::random(64);
            $customer->access_pin ??= self::generateAccessPin();
        });

        static::saving(function (Customer $customer): void {
            $customer->phone = filled($customer->phone) ? Phone::normalize((string) $customer->phone) : null;
            $customer->email = $customer->email !== null ? mb_strtolower(trim($customer->email)) : null;
            $customer->document_type = $customer->document_type !== null
                ? mb_strtoupper(trim($customer->document_type))
                : null;
            $customer->document_number = $customer->document_number !== null
                ? preg_replace('/\D+/', '', $customer->document_number)
                : null;
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ensureAccessPin(): string
    {
        if (is_string($this->access_pin) && preg_match('/^\d{6}$/', $this->access_pin)) {
            return $this->access_pin;
        }

        return $this->rotateAccessPin();
    }

    public function rotateAccessPin(): string
    {
        $pin = self::generateAccessPin();
        $this->forceFill(['access_pin' => $pin])->save();

        return $pin;
    }

    private static function generateAccessPin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
