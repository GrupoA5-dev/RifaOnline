<?php

namespace App\Console\Commands;

use App\Services\Payments\InterPixClient;
use App\Services\Payments\InterPixSettings;
use Illuminate\Console\Command;
use Throwable;

final class InterPixConnectionTest extends Command
{
    protected $signature = 'inter:pix-test';

    protected $description = 'Testa autenticação mTLS/OAuth do Pix Banco Inter sem criar cobrança.';

    public function handle(InterPixSettings $settings, InterPixClient $client): int
    {
        $status = $settings->configurationStatus();
        $this->line('Ambiente: '.$status['environment']);
        $this->line('Integração habilitada: '.($status['enabled'] ? 'SIM' : 'NÃO'));
        $this->line('Configuração mínima: '.($status['ready'] ? 'OK' : 'INCOMPLETA'));

        if (! $status['ready']) {
            return self::FAILURE;
        }

        try {
            $result = $client->testAuthentication(['cob.read']);
            $this->info('OAuth Banco Inter: OK');
            $this->line('Tipo: '.$result['token_type']);
            $this->line('Expira em: '.$result['expires_in'].' segundos');
            $this->line('Escopo: '.$result['scope']);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
