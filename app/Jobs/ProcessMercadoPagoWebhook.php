<?php

namespace App\Jobs;

use App\Actions\Payments\ApplyGatewayPayment;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMercadoPagoWebhook
{
    use Dispatchable;

    public function __construct(
        public readonly int $paymentEventId,
    ) {}

    public function handle(PaymentGateway $gateway, ApplyGatewayPayment $applyGatewayPayment): void
    {
        /** @var PaymentEvent|null $event */
        $event = PaymentEvent::query()->find($this->paymentEventId);

        if (! $event || $event->processed_at !== null) {
            return;
        }

        try {
            if (blank($event->resource_id)) {
                throw new \RuntimeException('Webhook sem data.id.');
            }

            $remote = $gateway->getPayment($event->resource_id);

            /** @var Payment|null $payment */
            $payment = Payment::query()
                ->where('gateway', 'mercadopago')
                ->where('gateway_payment_id', $remote->id)
                ->first();

            if (! $payment && filled($remote->externalReference)) {
                /** @var Order|null $order */
                $order = Order::query()->where('uuid', $remote->externalReference)->first();

                if ($order) {
                    $payment = $order->payments()
                        ->where('gateway', 'mercadopago')
                        ->latest('id')
                        ->first();

                    if (! $payment) {
                        $payment = $order->payments()->create([
                            'gateway' => 'mercadopago',
                            'gateway_payment_id' => $remote->id,
                            'status' => PaymentStatus::fromGateway($remote->status),
                            'amount_cents' => $order->total_cents,
                            'currency' => 'BRL',
                            'payment_method' => 'pix',
                            'external_reference' => $order->uuid,
                        ]);
                    }
                }
            }

            if (! $payment) {
                throw new \RuntimeException('Pagamento local não encontrado para o recurso notificado.');
            }

            $applyGatewayPayment->handle($payment, $remote);

            $event->forceFill([
                'processed_at' => now(),
                'processing_error' => null,
            ])->save();
        } catch (Throwable $e) {
            $event->forceFill([
                'processing_error' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();

            Log::error('Falha ao processar webhook Mercado Pago.', [
                'payment_event_id' => $event->getKey(),
                'resource_id' => $event->resource_id,
                'exception' => $e,
            ]);
        }
    }
}
