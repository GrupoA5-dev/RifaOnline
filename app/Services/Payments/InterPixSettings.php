<?php

namespace App\Services\Payments;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class InterPixSettings
{
    private const GROUP = 'payments.inter_pix';

    public function get(string $key, mixed $default = null): mixed
    {
        return SystemSetting::value(self::GROUP, $key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getSecret(string $key, ?string $default = null): ?string
    {
        $encrypted = $this->get($key);

        if (! is_string($encrypted) || $encrypted === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return $default;
        }
    }

    public function put(string $key, mixed $value, string $type = 'string'): void
    {
        SystemSetting::query()->updateOrCreate(
            ['group' => self::GROUP, 'key' => $key],
            [
                'value' => $value === null ? null : (string) $value,
                'type' => $type,
                'is_public' => false,
            ],
        );
    }

    public function putSecret(string $key, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $this->put($key, Crypt::encryptString($value), 'encrypted');
    }

    public function hasSecret(string $key): bool
    {
        return filled($this->getSecret($key));
    }

    public function webhookToken(): string
    {
        $token = trim((string) $this->get('webhook_token', ''));

        if ($token === '') {
            $token = Str::random(48);
            $this->put('webhook_token', $token, 'secret_token');
        }

        return $token;
    }

    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/webhooks/inter/pix/'.$this->webhookToken();
    }

    public function absoluteFilePath(string $key): ?string
    {
        $relative = $this->get($key);

        if (! is_string($relative) || $relative === '') {
            return null;
        }

        if (! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }


    public function paymentExpirationMinutes(): int
    {
        return max(1, min(30, (int) $this->get('payment_expiration_minutes', 5)));
    }

    public function expiryGraceSeconds(): int
    {
        return max(30, min(300, (int) $this->get('expiry_grace_seconds', 60)));
    }

    public function automaticEnabled(): bool
    {
        return $this->getBool('enabled') && $this->configurationStatus()['ready'];
    }

    public function configurationStatus(): array
    {
        $mode = (string) $this->get('certificate_mode', 'pem');
        $pfxReady = filled($this->absoluteFilePath('certificate_pfx_path'));
        $pemReady = filled($this->absoluteFilePath('certificate_crt_path'))
            && filled($this->absoluteFilePath('private_key_path'));

        return [
            'enabled' => $this->getBool('enabled'),
            'environment' => (string) $this->get('environment', 'sandbox'),
            'client_id' => filled($this->get('client_id')),
            'client_secret' => $this->hasSecret('client_secret'),
            'pix_key' => filled($this->get('pix_key')),
            'certificate_mode' => $mode,
            'certificate' => $mode === 'pfx' ? $pfxReady : $pemReady,
            'ready' => filled($this->get('client_id'))
                && $this->hasSecret('client_secret')
                && filled($this->get('pix_key'))
                && ($mode === 'pfx' ? $pfxReady : $pemReady),
        ];
    }
}
