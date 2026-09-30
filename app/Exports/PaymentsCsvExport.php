<?php

namespace App\Exports;

use App\Models\Payment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentsCsvExport
{
    public static function download(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Pedido',
                'Cliente',
                'WhatsApp',
                'Campanha',
                'Status',
                'Valor',
                'Gateway',
                'TXID',
                'Pago em',
                'Criado em',
            ], ';');

            Payment::query()
                ->with(['order.customer', 'order.raffle'])
                ->orderBy('id')
                ->chunk(200, function ($payments) use ($handle): void {
                    foreach ($payments as $payment) {
                        fputcsv($handle, [
                            $payment->order_id,
                            $payment->order?->customer?->name,
                            $payment->order?->customer?->phone,
                            $payment->order?->raffle?->title,
                            $payment->status?->label() ?? $payment->status,
                            number_format($payment->amount_cents / 100, 2, ',', '.'),
                            $payment->gateway,
                            $payment->external_reference,
                            optional($payment->paid_at)?->format('d/m/Y H:i'),
                            optional($payment->created_at)?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'pagamentos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
