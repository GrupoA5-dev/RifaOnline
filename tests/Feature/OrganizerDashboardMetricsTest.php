<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Raffle;
use App\Services\Organizers\OrganizerDashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_only_records_linked_to_each_organizer(): void
    {
        $habilis = Organizer::query()->create([
            'name' => 'Rifa Habilis',
            'slug' => 'rifa-habilis',
            'status' => 'active',
        ]);
        $other = Organizer::query()->create([
            'name' => 'Outra Organização',
            'slug' => 'outra-organizacao',
            'status' => 'active',
        ]);
        $empty = Organizer::query()->create([
            'name' => 'Sem Campanhas',
            'slug' => 'sem-campanhas',
            'status' => 'active',
        ]);

        $raffleHabilis = $this->raffle('habilis', $habilis->id);
        $raffleOther = $this->raffle('other', $other->id);
        $raffleLegacy = $this->raffle('legacy-untagged', null);

        // An existing customer can participate in multiple organizations,
        // but the participant count must be based on each organizer's orders.
        $shared = Customer::query()->create([
            'name' => 'Participante compartilhado',
            'phone' => '11999990001',
        ]);
        $otherCustomer = Customer::query()->create([
            'name' => 'Participante B',
            'phone' => '11999990002',
        ]);

        $habilisOrder = $this->order($raffleHabilis, $shared);
        $otherOrder = $this->order($raffleOther, $shared);
        $this->order($raffleOther, $otherCustomer);
        $legacyOrder = $this->order($raffleLegacy, $otherCustomer);

        $this->payment($habilisOrder);
        $this->payment($otherOrder);
        $this->payment($legacyOrder);

        $metrics = app(OrganizerDashboardMetrics::class);

        $this->assertSame([
            'campaigns' => 1,
            'orders' => 1,
            'participants' => 1,
            'payments' => 1,
        ], $metrics->for($habilis));

        $this->assertSame([
            'campaigns' => 1,
            'orders' => 2,
            'participants' => 2,
            'payments' => 1,
        ], $metrics->for($other));

        $this->assertSame([
            'campaigns' => 0,
            'orders' => 0,
            'participants' => 0,
            'payments' => 0,
        ], $metrics->for($empty));
    }

    private function raffle(string $slug, ?int $organizerId): Raffle
    {
        return Raffle::query()->create([
            'organizer_id' => $organizerId,
            'title' => 'Campanha ' . $slug,
            'slug' => $slug,
            'status' => 'draft',
            'ticket_price' => '1,00',
            'total_numbers' => 100,
        ]);
    }

    private function order(Raffle $raffle, Customer $customer): Order
    {
        return Order::query()->create([
            'raffle_id' => $raffle->id,
            'customer_id' => $customer->id,
            'status' => 'awaiting_payment',
            'quantity' => 1,
            'subtotal_cents' => 100,
            'total_cents' => 100,
        ]);
    }

    private function payment(Order $order): Payment
    {
        return Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'pix_static',
            'status' => 'pending',
            'amount_cents' => 100,
        ]);
    }
}
