<?php

namespace App\Services\Organizers;

use App\Models\Organizer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Raffle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only metrics for the current Filament organizer tenant.
 *
 * Every query is anchored on raffles.organizer_id. Rows with a NULL
 * organizer_id (legacy/untagged) are not visible to any organizer.
 * Customer records are intentionally not returned: the customers table
 * still has global phone uniqueness and requires a tenant-safe redesign.
 */
final class OrganizerDashboardMetrics
{
    /**
     * @return array{campaigns: int, orders: int, participants: int, payments: int}
     */
    public function for(Organizer $organizer): array
    {
        $raffles = Raffle::query()
            ->where('organizer_id', $organizer->getKey());

        $orders = Order::query()
            ->whereHas('raffle', fn (Builder $query): Builder => $query
                ->where('organizer_id', $organizer->getKey()));

        $payments = Payment::query()
            ->whereHas('order.raffle', fn (Builder $query): Builder => $query
                ->where('organizer_id', $organizer->getKey()));

        return [
            'campaigns' => $raffles->count(),
            'orders' => (clone $orders)->count(),
            'participants' => (clone $orders)->distinct()->count('customer_id'),
            'payments' => $payments->count(),
        ];
    }
}
