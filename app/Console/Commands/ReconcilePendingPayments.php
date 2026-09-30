<?php

namespace App\Console\Commands;

use App\Actions\Payments\ReconcileInterPixPayment;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcilePendingPayments extends Command
{
    protected $signature = 'payments:reconcile {--limit=100}';

    protected $description = 'Concilia pagamentos Pix Banco Inter pendentes como contingência ao webhook.';

    public function handle(ReconcileInterPixPayment $reconcile): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));
        $checked = 0;
        $approved = 0;
        $errors = 0;

        Payment::query()
            ->where('gateway', 'inter_pix')
            ->whereIn('status', [
                PaymentStatus::Creating->value,
                PaymentStatus::Pending->value,
                PaymentStatus::InProcess->value,
            ])
            ->where(function ($query): void {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subSeconds(30));
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Payment $payment) use ($reconcile, &$checked, &$approved, &$errors): void {
                $checked++;

                try {
                    $updated = $reconcile->handle($payment);
                    if ($updated->status === PaymentStatus::Approved) {
                        $approved++;
                    }
                } catch (Throwable $e) {
                    $errors++;
                    Log::warning('Falha na conciliação agendada Pix Inter.', [
                        'payment_id' => $payment->getKey(),
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        $this->info("Pix Inter conciliados: {$checked}; aprovados: {$approved}; falhas temporárias: {$errors}");

        return self::SUCCESS;
    }
}
