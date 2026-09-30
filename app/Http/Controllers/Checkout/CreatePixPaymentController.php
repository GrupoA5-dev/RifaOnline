<?php

namespace App\Http\Controllers\Checkout;

use App\Actions\Payments\CreatePixPayment;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreatePixPaymentController extends Controller
{
    public function __invoke(Request $request, string $orderUuid, CreatePixPayment $createPixPayment): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        /** @var Order $order */
        $order = Order::query()->where('uuid', $orderUuid)->firstOrFail();

        if (! hash_equals($order->public_token, $validated['token'])) {
            throw new NotFoundHttpException();
        }

        $payment = $createPixPayment->handle($order);

        return response()->json([
            'payment' => [
                'id' => $payment->uuid,
                'gateway' => $payment->gateway,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'amount_cents' => $payment->amount_cents,
                'qr_code' => $payment->qr_code,
                'qr_code_base64' => $payment->qr_code_base64,
                'ticket_url' => $payment->ticket_url,
                'txid' => $payment->external_reference,
                'confirmation_mode' => $payment->gateway === 'inter_pix' ? 'automatic' : 'manual',
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ],
        ]);
    }
}
