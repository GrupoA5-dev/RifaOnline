<?php

namespace App\Services\Payments;

final class MercadoPagoWebhookSignature
{
    public function validate(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId,
        string $secret,
    ): bool {
        if ($xSignature === null || trim($xSignature) === '' || $dataId === null || trim($dataId) === '' || trim($secret) === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key !== null && $value !== null) {
                $parts[trim($key)] = trim($value);
            }
        }

        $timestamp = $parts['ts'] ?? null;
        $receivedHash = $parts['v1'] ?? null;

        if ($timestamp === null || trim($timestamp) === '' || $receivedHash === null || trim($receivedHash) === '') {
            return false;
        }

        $manifest = 'id:'.strtolower(trim($dataId)).';';

        if ($xRequestId !== null && trim($xRequestId) !== '') {
            $manifest .= 'request-id:'.trim($xRequestId).';';
        }

        $manifest .= 'ts:'.trim($timestamp).';';

        $calculated = hash_hmac('sha256', $manifest, $secret);

        return hash_equals(strtolower($calculated), strtolower($receivedHash));
    }
}
