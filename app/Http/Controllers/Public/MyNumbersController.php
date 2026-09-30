<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class MyNumbersController extends Controller
{
    public function __invoke(string $token): Response
    {
        $customer = Customer::query()
            ->where('public_token', $token)
            ->firstOrFail();

        $orders = Order::query()
            ->where('customer_id', $customer->getKey())
            ->with(['raffle', 'tickets'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'quantity' => (int) $order->quantity,
                'total' => 'R$ '.Money::format((int) $order->total_cents),
                'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
                'created_at' => $order->created_at?->format('d/m/Y H:i'),
                'raffle' => [
                    'title' => $order->raffle?->title,
                    'url' => $order->raffle ? route('raffles.show', $order->raffle->slug) : null,
                ],
                'tickets' => $order->tickets
                    ->sortBy('number')
                    ->map(fn ($ticket) => $order->raffle?->formatNumber((int) $ticket->number) ?? (string) $ticket->number)
                    ->values(),
                'checkout_url' => route('checkout.show', [
                    'orderUuid' => $order->uuid,
                    'token' => $order->public_token,
                ]),
            ])
            ->values();

        return Inertia::render('MyNumbers/Index', [
            'customer' => [
                'name' => $customer->name,
                'token' => $customer->public_token,
            ],
            'orders' => $orders,
        ]);
    }
}
