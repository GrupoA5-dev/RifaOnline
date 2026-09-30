<?php

namespace App\Exports;

use App\Models\Customer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomersCsvExport
{
    public static function download(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Nome',
                'WhatsApp',
                'Email',
                'Documento',
                'Pedidos',
                'Cadastro',
            ], ';');

            Customer::query()
                ->withCount('orders')
                ->orderBy('id')
                ->chunk(200, function ($customers) use ($handle): void {
                    foreach ($customers as $customer) {
                        fputcsv($handle, [
                            $customer->name,
                            $customer->phone,
                            $customer->email,
                            $customer->document_number,
                            $customer->orders_count,
                            optional($customer->created_at)?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'clientes.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
