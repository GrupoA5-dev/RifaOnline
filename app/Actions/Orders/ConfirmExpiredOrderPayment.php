<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderTicketStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketAllocationStatus;
use App\Models\Order;
use App\Services\Support\AuditService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ConfirmExpiredOrderPayment
{
    public function handle(Order $order, ?CarbonInterface $paidAt = null): Order
    {
        $confirmed = DB::transaction(function () use ($order, $paidAt): Order {
            /** @var Order $locked */
            $locked = Order::query()
                ->lockForUpdate()
                ->with(['payments', 'tickets', 'allocations'])
                ->findOrFail($order->getKey());

            if ($locked->status !== OrderStatus::AwaitingPayment) {
                throw new RuntimeException('Somente pedidos aguardando pagamento podem ser confirmados.');
            }

            $payment = $locked->payments()
                ->where('status', PaymentStatus::Approved->value)
                ->latest('id')
                ->first();

            if (! $payment) {
                throw new RuntimeException('Não existe pagamento aprovado para este pedido.');
            }

            if ($locked->tickets()->count() !== (int) $locked->quantity) {
                throw new RuntimeException('Quantidade de números do pedido inválida.');
            }

            $effectivePaidAt = $paidAt ?? $payment->paid_at ?? now();

            $locked->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => $effectivePaidAt,
            ])->save();

            $locked->tickets()
                ->where('status', OrderTicketStatus::Reserved->value)
                ->update([
                    'status' => OrderTicketStatus::Paid->value,
                    'updated_at' => now(),
                ]);

            $locked->allocations()
                ->where('status', TicketAllocationStatus::Reserved->value)
                ->update([
                    'status' => TicketAllocationStatus::Paid->value,
                    'paid_at' => $effectivePaidAt,
                    'expires_at' => null,
                    'updated_at' => now(),
                ]);

            return $locked->fresh(['tickets', 'allocations', 'customer', 'raffle']) ?? $locked;
        }, 3);

        AuditService::record('order.expired_payment_confirmed', $confirmed, after: [
            'status' => $confirmed->status->value,
            'paid_at' => $confirmed->paid_at?->toIso8601String(),
        ]);

        return $confirmed;
    }
}
