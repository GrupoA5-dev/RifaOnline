<?php

namespace App\Console\Commands;

use App\Services\Payments\InterPixGateway;
use Illuminate\Console\Command;
use Throwable;

final class InterPixWebhookStatus extends Command
{
    protected $signature = 'inter:webhook-status';

    protected $description = 'Consulta no Banco Inter o webhook Pix cadastrado para a chave atual.';

    public function handle(InterPixGateway $gateway): int
    {
        try {
            $data = $gateway->getWebhook();
            $this->info('Webhook Pix localizado.');
            $this->line('URL: '.(string) ($data['webhookUrl'] ?? 'não informada'));
            $this->line('Criado em: '.(string) ($data['criacao'] ?? 'não informado'));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha ao consultar webhook: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
