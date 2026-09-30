<?php

namespace App\Console\Commands;

use App\Services\Payments\InterPixSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

final class Work7Status extends Command
{
    protected $signature = 'a5:work7-status';

    protected $description = 'Verifica a instalação do WORK 7 - Pix automático Banco Inter.';

    public function handle(InterPixSettings $settings): int
    {
        $checks = [
            'inter_client' => class_exists(\App\Services\Payments\InterPixClient::class),
            'inter_gateway' => class_exists(\App\Services\Payments\InterPixGateway::class),
            'create_inter_payment' => class_exists(\App\Actions\Payments\CreateInterPixPayment::class),
            'reconcile_inter_payment' => class_exists(\App\Actions\Payments\ReconcileInterPixPayment::class),
            'webhook_controller' => class_exists(\App\Http\Controllers\Webhooks\InterPixWebhookController::class),
            'webhook_route' => Route::has('webhooks.inter.pix'),
            'settings_ready' => (bool) $settings->configurationStatus()['ready'],
            'automatic_enabled' => $settings->automaticEnabled(),
        ];

        foreach ($checks as $name => $ok) {
            $this->line(str_pad($name, 28).($ok ? 'OK' : 'PENDING'));
        }

        $this->line('environment'.str_repeat(' ', 17).$settings->configurationStatus()['environment']);
        $this->line('payment_window'.str_repeat(' ', 14).$settings->paymentExpirationMinutes().' min');
        $this->line('webhook_url'.str_repeat(' ', 18).$settings->webhookUrl());

        if (! $checks['settings_ready']) {
            $this->warn('WORK 7 instalado, mas as credenciais/certificado ainda não estão completos.');
            return self::SUCCESS;
        }

        $this->info($checks['automatic_enabled']
            ? 'WORK 7 instalado e Pix Banco Inter habilitado.'
            : 'WORK 7 instalado. Ative o Pix Banco Inter no painel para usar o modo automático.');

        return self::SUCCESS;
    }
}
