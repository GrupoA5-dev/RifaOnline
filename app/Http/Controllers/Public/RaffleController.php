<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatus;
use App\Enums\RaffleStatus;
use App\Enums\TicketAllocationStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Raffle;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class RaffleController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $raffle = Raffle::query()
            ->where('slug', $slug)
            ->whereIn('status', [
                RaffleStatus::Active->value,
                RaffleStatus::SoldOut->value,
                RaffleStatus::Drawing->value,
                RaffleStatus::Finished->value,
            ])
            ->with([
                'prizes',
                'winners' => fn ($query) => $query
                    ->whereNotNull('announced_at')
                    ->where('announced_at', '<=', now())
                    ->with('prize'),
            ])
            ->withCount([
                'allocations as occupied_numbers',
                'allocations as paid_numbers' => fn (Builder $query) => $query->where('status', TicketAllocationStatus::Paid->value),
            ])
            ->firstOrFail();

        $ranking = Order::query()
            ->selectRaw('customer_id, SUM(quantity) as ticket_count')
            ->where('raffle_id', $raffle->getKey())
            ->where('status', OrderStatus::Paid->value)
            ->groupBy('customer_id')
            ->orderByDesc('ticket_count')
            ->limit(3)
            ->with('customer:id,name')
            ->get()
            ->map(fn (Order $order) => [
                'name' => $this->maskName($order->customer?->name),
                'tickets' => (int) $order->ticket_count,
            ])
            ->values();

        $paid = (int) ($raffle->paid_numbers ?? 0);
        $occupied = (int) ($raffle->occupied_numbers ?? 0);
        $available = max(0, (int) $raffle->total_numbers - $occupied);
        $total = max(1, (int) $raffle->total_numbers);

        return Inertia::render('Raffles/Show', [
            'raffle' => [
                'uuid' => $raffle->uuid,
                'title' => $raffle->title,
                'slug' => $raffle->slug,
                'description' => $raffle->description,
                'rules' => $raffle->rules,
                'status' => $raffle->status->value,
                'status_label' => $raffle->status->label(),
                'price_cents' => (int) $raffle->ticket_price_cents,
                'total_numbers' => (int) $raffle->total_numbers,
                'paid_numbers' => $paid,
                'available_numbers' => $available,
                'progress' => min(100, round(($paid / $total) * 100, 1)),
                'min_purchase' => (int) $raffle->min_purchase,
                'max_purchase' => $raffle->max_purchase ? (int) $raffle->max_purchase : null,
                'reservation_minutes' => (int) $raffle->reservation_minutes,
                'allocation_mode' => $raffle->allocation_mode->value,
                'checkout_fields' => [
                    'name' => (bool) $raffle->collect_name,
                    'email' => (bool) $raffle->collect_email,
                    // WhatsApp é sempre obrigatório: identifica o cliente e libera
                    // a consulta posterior dos números comprados.
                    'phone' => true,
                    'document' => (bool) $raffle->collect_document,
                ],
                'numbers_url' => route('raffles.numbers', $raffle->slug),
                'cover_url' => $this->coverUrl($raffle->cover_image),
                'organizer' => $raffle->organizer_name,
                'draw_mode' => $raffle->draw_mode,
                'draw_reference' => $raffle->draw_reference,
                'purchasable' => $raffle->isPurchasable() && $available > 0,
                'ends_at' => $raffle->ends_at?->toIso8601String(),
                'share_url' => route('raffles.show', $raffle->slug),
                'prizes' => $raffle->prizes->map(fn ($prize) => [
                    'position' => (int) $prize->position,
                    'title' => $prize->title,
                    'description' => $prize->description,
                    'image' => $this->coverUrl($prize->image),
                ])->values(),
                'winners' => $raffle->winners->map(fn ($winner) => [
                    'name' => $winner->winner_name,
                    'number' => $raffle->formatNumber((int) $winner->ticket_number),
                    'prize' => $winner->prize_title ?: $winner->prize?->title,
                    'announced_at' => $winner->announced_at?->format('d/m/Y H:i'),
                ])->values(),
                'ranking' => $ranking,
                'reserve_url' => route('raffles.reserve', $raffle->slug),
            ],
        ]);
    }

    private function maskName(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'Participante';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].' '.mb_substr((string) end($parts), 0, 1).'.';
    }

    private function coverUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}
