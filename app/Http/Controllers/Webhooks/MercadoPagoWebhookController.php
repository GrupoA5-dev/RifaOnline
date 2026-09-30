<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\Payments\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMercadoPagoWebhook;
use App\Models\PaymentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $payload = $request->json()->all();
        $dataId = (string) (
            $request->query->get('data.id')
            ?? data_get($payload, 'data.id')
            ?? ''
        );
        $requestId = $request->header('x-request-id');
        $signature = $request->header('x-signature');

        if (! $gateway->validateWebhookSignature($signature, $requestId, $dataId)) {
            return response()->json(['ok' => false], 401);
        }

        $eventType = (string) ($payload['action'] ?? $payload['type'] ?? 'payment.updated');
        $notificationId = (string) ($payload['id'] ?? '');
        $eventKey = hash('sha256', implode('|', [
            'mercadopago',
            $notificationId,
            $requestId ?? '',
            $dataId,
            $eventType,
        ]));

        $event = PaymentEvent::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'gateway' => 'mercadopago',
                'event_type' => $eventType,
                'resource_id' => $dataId,
                'request_id' => $requestId,
                'signature_valid' => true,
                'payload' => $payload,
            ],
        );

        $topic = (string) ($request->query('type') ?? $payload['type'] ?? '');

        if ($topic !== '' && $topic !== 'payment') {
            $event->forceFill([
                'processed_at' => now(),
                'processing_error' => 'Evento ignorado: tópico não é payment.',
            ])->save();

            return response()->json(['ok' => true]);
        }

        if ($event->processed_at === null) {
            ProcessMercadoPagoWebhook::dispatchAfterResponse($event->getKey());
        }

        return response()->json(['ok' => true]);
    }
}
