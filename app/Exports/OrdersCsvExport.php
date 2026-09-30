<?php

namespace App\Exports;

use App\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdersCsvExport
{
    public static function download(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Pedido',
                'Campanha',
                'Cliente',
                'WhatsApp',
                'Status',
                'Quantidade',
                'Total',
                'Pago em',
                'Cadastro',
            ], ';');

            Order::query()
                ->with(['customer', 'raffle'])
                ->orderBy('id')
                ->chunk(200, function ($orders) use ($handle): void {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->id,
                            $order->raffle?->title,
                            $order->customer?->name,
                            $order->customer?->phone,
                            $order->status?->label() ?? $order->status,
                            $order->quantity,
                            number_format($order->total_cents / 100, 2, ',', '.'),
                            optional($order->paid_at)?->format('d/m/Y H:i'),
                            optional($order->created_at)?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'pedidos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
