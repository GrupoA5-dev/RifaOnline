<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class Work3Status extends Command
{
    protected $signature = 'a5:work3-status';

    protected $description = 'Valida a instalação do WORK 3 (Pix / Mercado Pago).';

    public function handle(): int
    {
        $checks = [
            'payments' => Schema::hasTable('payments'),
            'payment_events' => Schema::hasTable('payment_events'),
            'customer_document' => Schema::hasColumns('customers', ['document_type', 'document_number']),
            'webhook_route' => Route::has('webhooks.mercadopago'),
            'access_token' => filled(config('mercadopago.access_token')),
            'webhook_secret' => filled(config('mercadopago.webhook_secret')),
        ];

        foreach ($checks as $label => $ok) {
            $this->line(sprintf('%-22s %s', $label, $ok ? 'OK' : 'PENDENTE'));
        }

        $this->newLine();
        $this->line('Webhook: '.(Route::has('webhooks.mercadopago') ? route('webhooks.mercadopago') : 'indisponível'));

        $codeInstalled = $checks['payments'] && $checks['payment_events'] && $checks['customer_document'] && $checks['webhook_route'];
        $credentialsReady = $checks['access_token'] && $checks['webhook_secret'];

        if (! $codeInstalled) {
            $this->error('WORK 3 incompleto: execute as migrations e confira o patch.');
            return self::FAILURE;
        }

        if (! $credentialsReady) {
            $this->warn('WORK 3 instalado. Falta configurar as credenciais Mercado Pago no .env.');
            return self::SUCCESS;
        }

        $this->info('WORK 3 instalado e credenciais configuradas.');

        return self::SUCCESS;
    }
}
