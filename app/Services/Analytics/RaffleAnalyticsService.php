<?php

namespace App\Services\Analytics;

use App\Models\Raffle;
use App\Models\RaffleEvent;
use Illuminate\Http\Request;

final class RaffleAnalyticsService
{
    public const EVENT_VIEW = 'view';

    public const CLICK_EVENTS = [
        'campaign_click',
        'purchase_click',
        'share_click',
        'copy_click',
        'whatsapp_share',
        'whatsapp_contact',
        'instagram_contact',
    ];

    public function recordView(Raffle $raffle, Request $request): void
    {
        $session = $request->session();
        $key = 'a5_raffle_view_'.$raffle->getKey();
        $lastViewedAt = (int) $session->get($key, 0);

        if ($lastViewedAt > 0 && $lastViewedAt >= now()->subMinutes(30)->timestamp) {
            return;
        }

        $this->record($raffle, self::EVENT_VIEW, $request);
        $session->put($key, now()->timestamp);
    }

    public function recordClick(Raffle $raffle, string $eventType, Request $request): void
    {
        if (! in_array($eventType, self::CLICK_EVENTS, true)) {
            return;
        }

        $this->record($raffle, $eventType, $request);
    }

    private function record(Raffle $raffle, string $eventType, Request $request): void
    {
        RaffleEvent::query()->create([
            'raffle_id' => $raffle->getKey(),
            'event_type' => $eventType,
            'session_hash' => $this->sessionHash($request),
        ]);
    }

    private function sessionHash(Request $request): ?string
    {
        $id = $request->session()->getId();

        if ($id === '') {
            return null;
        }

        return hash_hmac('sha256', $id, (string) config('app.key'));
    }
}
