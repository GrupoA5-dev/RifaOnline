<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderTicketStatus;
use App\Enums\TicketAllocationStatus;
use App\Models\Order;
use App\Services\Notifications\EmailNotificationService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MarkOrderPaid
{
    public function __construct(private readonly EmailNotificationService $emailNotifications) {}

    public function handle(Order $order, ?CarbonInterface $paidAt = null): Order
    {
        $justPaid = false;

        $paid = DB::transaction(function () use ($order, $paidAt, &$justPaid): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->status === OrderStatus::Paid) {
                return $locked->fresh(['tickets', 'allocations']) ?? $locked;
            }

            if ($locked->status !== OrderStatus::AwaitingPayment) {
                throw new RuntimeException('Somente pedidos aguardando pagamento podem ser marcados como pagos.');
            }

            $effectivePaidAt = $paidAt ? $paidAt->toImmutable() : now()->toImmutable();

            if ($locked->expires_at?->isPast() && $effectivePaidAt->greaterThan($locked->expires_at)) {
                throw new RuntimeException('O pagamento foi aprovado após a expiração do pedido. Revisão manual necessária.');
            }

            if ($locked->allocations()->where('status', TicketAllocationStatus::Reserved->value)->count() !== (int) $locked->quantity) {
                throw new RuntimeException('A quantidade de números reservados não corresponde ao pedido. Revisão manual necessária.');
            }

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
                    'expires_at' => null,
                    'paid_at' => $effectivePaidAt,
                    'updated_at' => now(),
                ]);

            $justPaid = true;

            return $locked->fresh(['tickets', 'allocations', 'raffle', 'customer']) ?? $locked;
        }, 3);

        if ($justPaid) {
            $this->emailNotifications->notifyPaymentConfirmed($paid);
        }

        return $paid;
    }
}
