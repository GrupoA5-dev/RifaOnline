<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Services\Analytics\RaffleAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RaffleEventController extends Controller
{
    public function __invoke(Request $request, string $slug, RaffleAnalyticsService $analytics): Response
    {
        $validated = $request->validate([
            'event' => ['required', 'string', Rule::in(RaffleAnalyticsService::CLICK_EVENTS)],
        ]);

        $raffle = Raffle::query()->where('slug', $slug)->firstOrFail();
        $analytics->recordClick($raffle, (string) $validated['event'], $request);

        return response()->noContent();
    }
}
