<?php

namespace App\Http\Controllers\Public;

use App\Enums\RaffleStatus;
use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Enums\TicketAllocationStatus;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $active = Raffle::query()
            ->where('status', RaffleStatus::Active->value)
            ->withCount([
                'allocations as paid_numbers' => fn (Builder $query) => $query->where('status', TicketAllocationStatus::Paid->value),
                'allocations as occupied_numbers',
            ])
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->get();

        $featured = $active->firstWhere('featured', true) ?? $active->first();
        $others = $featured
            ? $active->where('id', '!=', $featured->id)->values()
            : $active;

        $finished = Raffle::query()
            ->whereIn('status', [
                RaffleStatus::SoldOut->value,
                RaffleStatus::Drawing->value,
                RaffleStatus::Finished->value,
            ])
            ->withCount([
                'allocations as paid_numbers' => fn (Builder $query) => $query->where('status', TicketAllocationStatus::Paid->value),
                'allocations as occupied_numbers',
            ])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        return Inertia::render('Home', [
            'featured' => $featured ? $this->raffle($featured) : null,
            'raffles' => $others->map(fn (Raffle $raffle) => $this->raffle($raffle))->values(),
            'finished' => $finished->map(fn (Raffle $raffle) => $this->raffle($raffle))->values(),
        ]);
    }

    private function raffle(Raffle $raffle): array
    {
        $occupied = (int) ($raffle->occupied_numbers ?? 0);
        $paid = (int) ($raffle->paid_numbers ?? 0);
        $total = max(1, (int) $raffle->total_numbers);

        return [
            'uuid' => $raffle->uuid,
            'title' => $raffle->title,
            'slug' => $raffle->slug,
            'status' => $raffle->status->value,
            'status_label' => $raffle->status->label(),
            'price_cents' => (int) $raffle->ticket_price_cents,
            'cover_url' => $this->coverUrl($raffle->cover_image),
            'organizer' => $raffle->organizer_name,
            'draw_reference' => $raffle->draw_reference,
            'total_numbers' => (int) $raffle->total_numbers,
            'paid_numbers' => $paid,
            'available_numbers' => max(0, (int) $raffle->total_numbers - $occupied),
            'progress' => min(100, round(($paid / $total) * 100, 1)),
            'purchasable' => $raffle->isPurchasable(),
            'url' => route('raffles.show', $raffle->slug),
            'analytics_url' => route('raffles.events', $raffle->slug),
        ];
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
