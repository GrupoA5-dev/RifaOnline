<?php

namespace App\Services\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class AuditService
{
    public static function record(
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?int $actorId = null,
    ): void {
        try {
            $request = request();

            AuditLog::query()->create([
                'user_id' => $actorId ?? Auth::id(),
                'action' => $action,
                'entity_type' => $entity ? $entity::class : null,
                'entity_id' => $entity?->getKey(),
                'before_data' => self::sanitize($before),
                'after_data' => self::sanitize($after),
                'ip_address' => $request?->ip(),
                'user_agent' => mb_substr((string) $request?->userAgent(), 0, 1000),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Auditoria nunca deve derrubar a operação principal.
        }
    }

    private static function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if (Str::contains($normalizedKey, [
                'password',
                'secret',
                'token',
                'recovery',
                'private_key',
                'access_pin',
                'certificate_password',
                'client_secret',
            ])) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            }
        }

        return $data;
    }
}
