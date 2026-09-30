<?php

namespace App\Http\Controllers\Public;

use App\Enums\AllocationMode;
use App\Enums\RaffleStatus;
use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailableNumbersController extends Controller
{
    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $raffle = Raffle::query()
            ->where('slug', $slug)
            ->whereIn('status', [RaffleStatus::Active->value, RaffleStatus::SoldOut->value])
            ->firstOrFail();

        abort_unless($raffle->allocation_mode === AllocationMode::Manual, 404);

        $perPage = max(20, min(200, (int) $request->integer('per_page', 100)));
        $total = (int) $raffle->total_numbers;
        $lastPage = max(1, (int) ceil(max(1, $total) / $perPage));
        $page = max(1, min((int) $request->integer('page', 1), $lastPage));
        $start = ($page - 1) * $perPage;
        $end = min($total - 1, $start + $perPage - 1);

        $occupied = DB::table('ticket_allocations')
            ->where('raffle_id', $raffle->getKey())
            ->whereBetween('number', [$start, $end])
            ->pluck('number')
            ->mapWithKeys(fn ($number) => [(int) $number => true])
            ->all();

        $numbers = [];

        for ($number = $start; $number <= $end; $number++) {
            $numbers[] = [
                'value' => $number,
                'label' => $raffle->formatNumber($number),
                'available' => ! isset($occupied[$number]),
            ];
        }

        return response()->json([
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'total_numbers' => $total,
            'numbers' => $numbers,
        ]);
    }
}
