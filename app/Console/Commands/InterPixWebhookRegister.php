<?php

namespace App\Console\Commands;

use App\Services\Payments\InterPixGateway;
use App\Services\Payments\InterPixSettings;
use Illuminate\Console\Command;
use Throwable;

final class InterPixWebhookRegister extends Command
{
    protected $signature = 'inter:webhook-register';

    protected $description = 'Cadastra no Banco Inter o webhook Pix desta aplicação.';

    public function handle(InterPixSettings $settings, InterPixGateway $gateway): int
    {
        if (! $settings->configurationStatus()['ready']) {
            $this->error('Configuração do Banco Inter incompleta.');
            return self::FAILURE;
        }

        $url = $settings->webhookUrl();
        $this->line('Webhook: '.$url);

        try {
            $gateway->registerWebhook($url);
            $this->info('Webhook Pix cadastrado no Banco Inter.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha ao cadastrar webhook: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
