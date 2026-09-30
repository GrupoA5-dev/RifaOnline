<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\GatewayPaymentData;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class MercadoPagoGateway implements PaymentGateway
{
    public function __construct(
        private readonly MercadoPagoWebhookSignature $signatureValidator,
    ) {}

    public function createPixPayment(Payment $payment): GatewayPaymentData
    {
        $payment->loadMissing(['order.raffle', 'order.customer']);
        $order = $payment->order;
        $customer = $order->customer;

        if (blank($customer->email)) {
            throw new PaymentGatewayException('O cliente precisa ter e-mail para gerar o Pix.');
        }

        if (blank($customer->document_type) || blank($customer->document_number)) {
            throw new PaymentGatewayException('O cliente precisa ter CPF/documento cadastrado para gerar o Pix.');
        }

        $nameParts = preg_split('/\s+/', trim($customer->name), 2) ?: [];
        $firstName = $nameParts[0] ?? $customer->name;
        $lastName = $nameParts[1] ?? '';

        $body = [
            'transaction_amount' => round($payment->amount_cents / 100, 2),
            'description' => mb_substr($order->raffle->title, 0, 255),
            'payment_method_id' => 'pix',
            'external_reference' => $order->uuid,
            'notification_url' => config('mercadopago.notification_url') ?: route('webhooks.mercadopago'),
            'metadata' => [
                'order_id' => $order->getKey(),
                'order_uuid' => $order->uuid,
            ],
            'payer' => [
                'email' => $customer->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'identification' => [
                    'type' => mb_strtoupper($customer->document_type),
                    'number' => $customer->document_number,
                ],
            ],
        ];

        $response = $this->request()
            ->withHeaders(['X-Idempotency-Key' => $payment->idempotency_key])
            ->post('/v1/payments', $body);

        return $this->toData($response);
    }

    public function getPayment(string $gatewayPaymentId): GatewayPaymentData
    {
        $response = $this->request()->get('/v1/payments/'.rawurlencode($gatewayPaymentId));

        return $this->toData($response);
    }

    public function validateWebhookSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId,
    ): bool {
        return $this->signatureValidator->validate(
            $xSignature,
            $xRequestId,
            $dataId,
            (string) config('mercadopago.webhook_secret'),
        );
    }

    private function request(): PendingRequest
    {
        $token = (string) config('mercadopago.access_token');

        if (blank($token)) {
            throw new PaymentGatewayException('MERCADOPAGO_ACCESS_TOKEN não configurado.');
        }

        return Http::baseUrl((string) config('mercadopago.base_url'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('mercadopago.connect_timeout', 5))
            ->timeout((int) config('mercadopago.timeout', 15));
    }

    private function toData(Response $response): GatewayPaymentData
    {
        if (! $response->successful()) {
            $message = (string) ($response->json('message') ?? $response->json('error') ?? 'Falha na comunicação com o Mercado Pago.');
            throw new PaymentGatewayException("Mercado Pago HTTP {$response->status()}: {$message}");
        }

        $payload = $response->json();

        if (! is_array($payload) || blank($payload['id'] ?? null)) {
            throw new PaymentGatewayException('Resposta inválida recebida do Mercado Pago.');
        }

        try {
            $amountCents = Money::toCents((string) ($payload['transaction_amount'] ?? '0'));
        } catch (Throwable) {
            $amountCents = 0;
        }

        return new GatewayPaymentData(
            id: (string) $payload['id'],
            status: (string) ($payload['status'] ?? 'unknown'),
            statusDetail: isset($payload['status_detail']) ? (string) $payload['status_detail'] : null,
            amountCents: $amountCents,
            externalReference: isset($payload['external_reference']) ? (string) $payload['external_reference'] : null,
            qrCode: data_get($payload, 'point_of_interaction.transaction_data.qr_code'),
            qrCodeBase64: data_get($payload, 'point_of_interaction.transaction_data.qr_code_base64'),
            ticketUrl: data_get($payload, 'point_of_interaction.transaction_data.ticket_url'),
            expiresAt: $this->date(data_get($payload, 'date_of_expiration')),
            paidAt: $this->date(data_get($payload, 'date_approved')),
        );
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }
}
