<?php

namespace Tests\Unit;

use App\Services\Payments\StaticPixPayload;
use Tests\TestCase;

class StaticPixPayloadTest extends TestCase
{
    public function test_it_matches_the_official_bcb_static_example_without_amount(): void
    {
        $payload = app(StaticPixPayload::class)->make(
            key: '123e4567-e12b-12d1-a456-426655440000',
            merchantName: 'Fulano de Tal',
            merchantCity: 'BRASILIA',
            amountCents: null,
            txid: '***',
        );

        $this->assertSame(
            '00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-4266554400005204000053039865802BR5913Fulano de Tal6008BRASILIA62070503***63041D3D',
            $payload,
        );
    }

    public function test_it_generates_a_static_pix_with_amount_and_unique_txid(): void
    {
        $payload = app(StaticPixPayload::class)->make(
            key: 'teste@example.com',
            merchantName: 'A5 Rifas',
            merchantCity: 'CAMPINA GRANDE',
            amountCents: 100,
            txid: 'A5PEDIDO123',
        );

        $this->assertStringContainsString('0014br.gov.bcb.pix', $payload);
        $this->assertStringContainsString('54041.00', $payload);
        $this->assertStringContainsString('A5PEDIDO123', $payload);
        $this->assertMatchesRegularExpression('/6304[A-F0-9]{4}$/', $payload);
    }
}
