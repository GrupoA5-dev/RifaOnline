<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutController extends Controller
{
    public function __invoke(string $orderUuid, string $token): Response
    {
        /** @var Order $order */
        $order = Order::query()
            ->where('uuid', $orderUuid)
            ->with(['raffle', 'customer', 'tickets', 'payments' => fn ($query) => $query->latest('id')])
            ->firstOrFail();

        if (strlen($token) !== 64 || ! hash_equals($order->public_token, $token)) {
            throw new NotFoundHttpException();
        }

        /** @var Payment|null $payment */
        $payment = $order->payments->first();
        $customerAccessPin = $order->customer->ensureAccessPin();

        return Inertia::render('Checkout/Pix', [
            'order' => [
                'uuid' => $order->uuid,
                'token' => $order->public_token,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'quantity' => (int) $order->quantity,
                'total_cents' => (int) $order->total_cents,
                'expires_at' => $order->expires_at?->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
                'customer' => [
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                    'access_pin' => $customerAccessPin,
                    'access_token' => $order->customer->public_token,
                    'access_url' => route('my-numbers.show', $order->customer->public_token),
                    'lookup_url' => route('my-numbers.lookup'),
                ],
                'raffle' => [
                    'title' => $order->raffle->title,
                    'slug' => $order->raffle->slug,
                    'url' => route('raffles.show', $order->raffle->slug),
                ],
                'tickets' => $order->tickets
                    ->sortBy('number')
                    ->map(fn ($ticket) => $order->raffle->formatNumber((int) $ticket->number))
                    ->values(),
            ],
            'payment' => $payment ? [
                'gateway' => $payment->gateway,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'qr_code' => $payment->qr_code,
                'qr_code_base64' => $payment->qr_code_base64,
                'ticket_url' => $payment->ticket_url,
                'txid' => $payment->external_reference,
                'confirmation_mode' => $payment->gateway === 'inter_pix' ? 'automatic' : 'manual',
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ] : null,
            'urls' => [
                'create_payment' => route('checkout.orders.pix', $order->uuid),
                'payment_status' => route('checkout.orders.payment-status', $order->uuid),
            ],
        ]);
    }
}
