<?php

namespace App\Services\Payments;

use App\Models\Order;
use Carbon\CarbonImmutable;
use RuntimeException;

final class InterPixGateway
{
    public function __construct(
        private readonly InterPixClient $client,
        private readonly InterPixSettings $settings,
    ) {}

    /** @return array<string,mixed> */
    public function createImmediateCharge(string $txid, Order $order, int $expiresInSeconds): array
    {
        $pixKey = trim((string) $this->settings->get('pix_key', ''));
        if ($pixKey === '') {
            throw new RuntimeException('A chave Pix do Banco Inter não está configurada.');
        }

        $payload = [
            'calendario' => [
                'expiracao' => max(60, min(3600, $expiresInSeconds)),
            ],
            'valor' => [
                'original' => number_format(((int) $order->total_cents) / 100, 2, '.', ''),
            ],
            'chave' => $pixKey,
            'solicitacaoPagador' => 'A5 RIFAS - Pedido '.strtoupper(substr((string) $order->uuid, 0, 8)),
        ];

        $response = $this->client->put('/pix/v2/cob/'.rawurlencode($txid), $payload, ['cob.write']);
        $this->client->assertSuccessful($response, 'criação da cobrança Pix');

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('O Banco Inter retornou a cobrança Pix em formato inesperado.');
        }

        return $data;
    }

    /** @return array<string,mixed> */
    public function getCharge(string $txid): array
    {
        $response = $this->client->get('/pix/v2/cob/'.rawurlencode($txid), [], ['cob.read']);
        $this->client->assertSuccessful($response, 'consulta da cobrança Pix');

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('O Banco Inter retornou a consulta Pix em formato inesperado.');
        }

        return $data;
    }

    /** @return array<string,mixed> */
    public function registerWebhook(string $url): array
    {
        $pixKey = trim((string) $this->settings->get('pix_key', ''));
        if ($pixKey === '') {
            throw new RuntimeException('A chave Pix do Banco Inter não está configurada.');
        }

        $response = $this->client->put('/pix/v2/webhook/'.rawurlencode($pixKey), [
            'webhookUrl' => $url,
        ], ['webhook.write']);

        $this->client->assertSuccessful($response, 'cadastro do webhook Pix');

        return is_array($response->json()) ? $response->json() : ['ok' => true];
    }

    /** @return array<string,mixed> */
    public function getWebhook(): array
    {
        $pixKey = trim((string) $this->settings->get('pix_key', ''));
        if ($pixKey === '') {
            throw new RuntimeException('A chave Pix do Banco Inter não está configurada.');
        }

        $response = $this->client->get('/pix/v2/webhook/'.rawurlencode($pixKey), [], ['webhook.read']);
        $this->client->assertSuccessful($response, 'consulta do webhook Pix');

        return is_array($response->json()) ? $response->json() : [];
    }

    public function txidForOrder(Order $order): string
    {
        return 'A5RIFA'.strtoupper(substr(hash('sha256', (string) $order->uuid), 0, 29));
    }

    public function chargeExpiresAt(array $charge, ?CarbonImmutable $fallback = null): ?CarbonImmutable
    {
        $createdAt = $charge['calendario']['criacao'] ?? null;
        $seconds = (int) ($charge['calendario']['expiracao'] ?? 0);

        if (is_string($createdAt) && $createdAt !== '' && $seconds > 0) {
            try {
                return CarbonImmutable::parse($createdAt)->addSeconds($seconds);
            } catch (\Throwable) {
                // usa o fallback abaixo
            }
        }

        return $fallback;
    }
}
