<?php

namespace Tests\Unit;

use App\Services\Payments\MercadoPagoWebhookSignature;
use PHPUnit\Framework\TestCase;

class MercadoPagoWebhookSignatureTest extends TestCase
{
    public function test_it_validates_the_documented_hmac_manifest(): void
    {
        $secret = 'secret-test';
        $dataId = 'PAYABC123';
        $requestId = 'request-456';
        $timestamp = '1781009491';
        $manifest = 'id:payabc123;request-id:request-456;ts:1781009491;';
        $hash = hash_hmac('sha256', $manifest, $secret);
        $header = "ts={$timestamp},v1={$hash}";

        $validator = new MercadoPagoWebhookSignature();

        $this->assertTrue($validator->validate($header, $requestId, $dataId, $secret));
        $this->assertFalse($validator->validate($header, $requestId, $dataId, 'wrong-secret'));
    }
}
