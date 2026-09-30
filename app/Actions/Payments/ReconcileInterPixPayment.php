<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\InterPixGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReconcileInterPixPayment
{
    public function __construct(
        private readonly InterPixGateway $gateway,
        private readonly MarkOrderPaid $markOrderPaid,
    ) {}

    public function handle(Payment $payment): Payment
    {
        if ($payment->gateway !== 'inter_pix') {
            return $payment;
        }

        $txid = trim((string) $payment->external_reference);
        if ($txid === '') {
            throw new RuntimeException('Pagamento Inter sem txid para conciliação.');
        }

        $charge = $this->gateway->getCharge($txid);
        $remoteTxid = (string) ($charge['txid'] ?? $txid);
        if ($remoteTxid !== '' && ! hash_equals($txid, $remoteTxid)) {
            throw new RuntimeException('O txid retornado pelo Banco Inter não corresponde ao pagamento local.');
        }

        $pixItems = is_array($charge['pix'] ?? null) ? $charge['pix'] : [];
        $status = strtoupper((string) ($charge['status'] ?? ''));
        $paidCents = 0;
        $paidAt = null;
        $endToEndId = null;

        foreach ($pixItems as $pix) {
            if (! is_array($pix)) {
                continue;
            }

            if (isset($pix['txid']) && (string) $pix['txid'] !== '' && ! hash_equals($txid, (string) $pix['txid'])) {
                continue;
            }

            $paidCents += $this->moneyToCents((string) ($pix['valor'] ?? '0'));
            $endToEndId ??= filled($pix['endToEndId'] ?? null) ? (string) $pix['endToEndId'] : null;

            if (filled($pix['horario'] ?? null)) {
                try {
                    $candidate = CarbonImmutable::parse((string) $pix['horario']);
                    $paidAt = $paidAt === null || $candidate->greaterThan($paidAt) ? $candidate : $paidAt;
                } catch (\Throwable) {
                    // Mantém horário nulo; a confirmação continua baseada na consulta remota.
                }
            }
        }

        $localAmount = (int) $payment->amount_cents;
        $newStatus = match (true) {
            $paidCents > 0 && $paidCents === $localAmount => PaymentStatus::Approved,
            $paidCents > 0 && $paidCents !== $localAmount => PaymentStatus::InProcess,
            $status === 'ATIVA' => PaymentStatus::Pending,
            in_array($status, ['REMOVIDA_PELO_USUARIO_RECEBEDOR', 'REMOVIDA_PELO_PSP'], true) => PaymentStatus::Cancelled,
            $status === 'CONCLUIDA' => PaymentStatus::InProcess,
            default => PaymentStatus::Unknown,
        };

        $detail = match (true) {
            $newStatus === PaymentStatus::Approved => 'Pix confirmado automaticamente pelo Banco Inter',
            $paidCents > 0 && $paidCents !== $localAmount => 'Pix recebido com valor divergente; revisão manual necessária',
            $newStatus === PaymentStatus::Cancelled => 'Cobrança removida no Banco Inter',
            $newStatus === PaymentStatus::Pending => 'Aguardando pagamento no Banco Inter',
            default => 'Status Inter: '.($status !== '' ? $status : 'desconhecido'),
        };

        /** @var Payment $updated */
        $updated = DB::transaction(function () use ($payment, $newStatus, $detail, $endToEndId, $paidAt): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->with('order')->findOrFail($payment->getKey());

            $locked->forceFill([
                'gateway_payment_id' => $endToEndId ?? $locked->gateway_payment_id,
                'status' => $newStatus,
                'status_detail' => mb_substr($detail, 0, 120),
                'paid_at' => $newStatus === PaymentStatus::Approved ? ($paidAt ?? $locked->paid_at ?? now()) : $locked->paid_at,
                'last_synced_at' => now(),
            ])->save();

            return $locked;
        }, 3);

        if ($updated->status === PaymentStatus::Approved) {
            $this->markOrderPaid->handle($updated->order, $updated->paid_at);
        }

        return $updated->fresh(['order']) ?? $updated;
    }

    private function moneyToCents(string $value): int
    {
        $value = str_replace(',', '.', trim($value));
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            return 0;
        }

        [$reais, $centavos] = array_pad(explode('.', $value, 2), 2, '0');
        $centavos = str_pad(substr($centavos, 0, 2), 2, '0');

        return ((int) $reais * 100) + (int) $centavos;
    }
}
