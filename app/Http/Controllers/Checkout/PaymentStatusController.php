<?php

namespace App\Http\Controllers\Checkout;

use App\Actions\Payments\ReconcileInterPixPayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class PaymentStatusController extends Controller
{
    public function __invoke(Request $request, string $orderUuid, ReconcileInterPixPayment $reconcile): JsonResponse
    {
        $token = (string) $request->header('X-Order-Token', '');

        /** @var Order $order */
        $order = Order::query()->where('uuid', $orderUuid)->firstOrFail();

        if (strlen($token) !== 64 || ! hash_equals($order->public_token, $token)) {
            throw new NotFoundHttpException();
        }

        /** @var Payment|null $payment */
        $payment = $order->payments()->latest('id')->first();

        if ($payment
            && $payment->gateway === 'inter_pix'
            && in_array($payment->status, [PaymentStatus::Creating, PaymentStatus::Pending, PaymentStatus::InProcess], true)
            && ($payment->last_synced_at === null || $payment->last_synced_at->lte(now()->subSeconds(15)))) {
            try {
                $payment = $reconcile->handle($payment);
                $order->refresh();
            } catch (Throwable $e) {
                Log::debug('Consulta de contingência do Pix Inter falhou.', [
                    'payment_id' => $payment->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'order' => [
                'status' => $order->status->value,
                'paid_at' => $order->paid_at?->toIso8601String(),
            ],
            'payment' => $payment ? [
                'gateway' => $payment->gateway,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'txid' => $payment->external_reference,
                'confirmation_mode' => $payment->gateway === 'inter_pix' ? 'automatic' : 'manual',
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
