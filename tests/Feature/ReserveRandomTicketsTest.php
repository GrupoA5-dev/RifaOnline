<?php

namespace Tests\Feature;

use App\Actions\Orders\ExpireOrderReservation;
use App\Actions\Raffles\ReserveRandomTickets;
use App\Enums\AllocationMode;
use App\Enums\OrderStatus;
use App\Enums\OrderTicketStatus;
use App\Enums\RaffleStatus;
use App\Models\Customer;
use App\Models\Raffle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReserveRandomTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reserves_unique_numbers_and_preserves_history_on_expiration(): void
    {
        $raffle = Raffle::query()->create([
            'title' => 'Teste',
            'slug' => 'teste',
            'status' => RaffleStatus::Active,
            'ticket_price' => '1,00',
            'total_numbers' => 1000,
            'number_digits' => 4,
            'min_purchase' => 1,
            'max_purchase' => 100,
            'allocation_mode' => AllocationMode::Random,
            'reservation_minutes' => 15,
            'draw_mode' => 'manual',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'phone' => '11999999999',
        ]);

        $action = app(ReserveRandomTickets::class);
        $first = $action->handle($raffle, $customer, 20);
        $second = $action->handle($raffle, $customer, 20);

        $allNumbers = $first->tickets->pluck('number')->merge($second->tickets->pluck('number'));

        $this->assertCount(40, $allNumbers);
        $this->assertCount(40, $allNumbers->unique());
        $this->assertDatabaseCount('ticket_allocations', 40);

        $first->forceFill(['expires_at' => now()->subMinute()])->save();
        $expired = app(ExpireOrderReservation::class)->handle($first);

        $this->assertTrue($expired);
        $this->assertSame(OrderStatus::Expired, $first->fresh()->status);
        $this->assertDatabaseCount('ticket_allocations', 20);
        $this->assertSame(
            20,
            $first->tickets()->where('status', OrderTicketStatus::Released->value)->count(),
        );
    }
}
