<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ReconcileInterPixPayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\InterPixSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class InterPixWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, ReconcileInterPixPayment $reconcile, InterPixSettings $settings): JsonResponse
    {
        if (! hash_equals($settings->webhookToken(), $token)) {
            abort(404);
        }

        $payload = $request->json()->all();
        $items = is_array($payload['pix'] ?? null) ? $payload['pix'] : [];
        $processed = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $txid = trim((string) ($item['txid'] ?? ''));
            if ($txid === '') {
                continue;
            }

            $eventKey = hash('sha256', implode('|', [
                $txid,
                (string) ($item['endToEndId'] ?? ''),
                (string) ($item['horario'] ?? ''),
                (string) ($item['valor'] ?? ''),
            ]));

            $event = PaymentEvent::query()->firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'gateway' => 'inter_pix',
                    'event_type' => 'pix.received',
                    'resource_id' => $txid,
                    'request_id' => (string) $request->header('x-request-id', ''),
                    'signature_valid' => false,
                    'payload' => [
                        'pix' => $item,
                        'x_conta_corrente' => (string) $request->header('x-conta-corrente', ''),
                    ],
                ],
            );

            if ($event->processed_at !== null) {
                continue;
            }

            /** @var Payment|null $payment */
            $payment = Payment::query()
                ->where('gateway', 'inter_pix')
                ->where('external_reference', $txid)
                ->latest('id')
                ->first();

            if (! $payment) {
                $event->forceFill([
                    'processed_at' => now(),
                    'processing_error' => 'Pagamento local não localizado para o txid recebido.',
                ])->save();
                continue;
            }

            try {
                $reconcile->handle($payment);
                $event->forceFill([
                    // O callback não é usado isoladamente como prova de pagamento.
                    // Este flag só fica true depois da confirmação ativa na API Inter.
                    'signature_valid' => true,
                    'processed_at' => now(),
                    'processing_error' => null,
                ])->save();
                $processed++;
            } catch (Throwable $e) {
                $event->forceFill([
                    'processing_error' => mb_substr($e->getMessage(), 0, 2000),
                ])->save();

                Log::warning('Falha ao conciliar callback Pix Banco Inter.', [
                    'txid' => $txid,
                    'payment_id' => $payment->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'received' => count($items),
            'processed' => $processed,
        ]);
    }
}
