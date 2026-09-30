<?php

namespace App\Console\Commands;

use App\Actions\Orders\ExpireOrderReservation;
use App\Actions\Payments\ReconcileInterPixPayment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\InterPixSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpireRaffleReservations extends Command
{
    protected $signature = 'raffles:expire-reservations';

    protected $description = 'Expira reservas vencidas com conferência final do Pix automático antes de liberar números.';

    public function handle(
        ExpireOrderReservation $expire,
        ReconcileInterPixPayment $reconcile,
        InterPixSettings $settings,
    ): int {
        $expired = 0;
        $manualReview = 0;
        $interDeferred = 0;
        $interPaid = 0;

        Order::query()
            ->where('status', OrderStatus::AwaitingPayment->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($expire, $reconcile, $settings, &$expired, &$manualReview, &$interDeferred, &$interPaid): void {
                foreach ($orders as $order) {
                    $hasStaticPix = $order->payments()
                        ->where('gateway', 'pix_static')
                        ->where('status', PaymentStatus::Pending->value)
                        ->exists();

                    if ($hasStaticPix) {
                        $manualReview++;
                        continue;
                    }

                    /** @var Payment|null $interPayment */
                    $interPayment = $order->payments()
                        ->where('gateway', 'inter_pix')
                        ->whereIn('status', [
                            PaymentStatus::Creating->value,
                            PaymentStatus::Pending->value,
                            PaymentStatus::InProcess->value,
                            PaymentStatus::Approved->value,
                        ])
                        ->latest('id')
                        ->first();

                    if ($interPayment) {
                        if ($order->expires_at->addSeconds($settings->expiryGraceSeconds())->isFuture()) {
                            $interDeferred++;
                            continue;
                        }

                        try {
                            $updated = $reconcile->handle($interPayment);
                            $order->refresh();

                            if ($updated->status === PaymentStatus::Approved || $order->status === OrderStatus::Paid) {
                                $interPaid++;
                                continue;
                            }
                        } catch (Throwable $e) {
                            // Em indisponibilidade do banco, não liberamos números para evitar dupla venda.
                            $interDeferred++;
                            Log::warning('Reserva não liberada porque a conferência final do Pix Inter falhou.', [
                                'order_id' => $order->getKey(),
                                'payment_id' => $interPayment->getKey(),
                                'error' => $e->getMessage(),
                            ]);
                            continue;
                        }
                    }

                    if ($expire->handle($order)) {
                        $expired++;
                    }
                }
            });

        $this->info("Expiradas: {$expired}; Pix estático em revisão: {$manualReview}; Pix Inter aguardando conferência: {$interDeferred}; Pix Inter confirmados: {$interPaid}");

        return self::SUCCESS;
    }
}
