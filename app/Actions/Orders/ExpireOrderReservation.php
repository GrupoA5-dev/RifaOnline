<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderTicketStatus;
use App\Enums\TicketAllocationStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class ExpireOrderReservation
{
    public function handle(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            /** @var Order|null $locked */
            $locked = Order::query()->lockForUpdate()->find($order->getKey());

            if (! $locked) {
                return false;
            }

            if ($locked->status !== OrderStatus::AwaitingPayment) {
                return false;
            }

            if ($locked->expires_at === null || $locked->expires_at->isFuture()) {
                return false;
            }

            $locked->forceFill([
                'status' => OrderStatus::Expired,
            ])->save();

            $locked->tickets()
                ->where('status', OrderTicketStatus::Reserved->value)
                ->update([
                    'status' => OrderTicketStatus::Released->value,
                    'updated_at' => now(),
                ]);

            $locked->allocations()
                ->where('status', TicketAllocationStatus::Reserved->value)
                ->delete();

            return true;
        }, 3);
    }
}
