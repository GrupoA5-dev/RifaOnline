<?php

namespace App\Console\Commands;

use App\Services\Payments\InterPixSettings;
use Illuminate\Console\Command;

class InterPixSettingsStatus extends Command
{
    protected $signature = 'a5:inter-pix-settings-status';
    protected $description = 'Verifica a configuração administrativa do Pix Banco Inter sem exibir segredos.';

    public function handle(InterPixSettings $settings): int
    {
        $status = $settings->configurationStatus();

        $this->line('enabled               '.($status['enabled'] ? 'YES' : 'NO'));
        $this->line('environment           '.$status['environment']);
        $this->line('client_id             '.($status['client_id'] ? 'OK' : 'PENDING'));
        $this->line('client_secret         '.($status['client_secret'] ? 'OK' : 'PENDING'));
        $this->line('pix_key               '.($status['pix_key'] ? 'OK' : 'PENDING'));
        $this->line('certificate_mode      '.$status['certificate_mode']);
        $this->line('certificate           '.($status['certificate'] ? 'OK' : 'PENDING'));
        $this->newLine();
        $this->line('Webhook: '.$settings->webhookUrl());

        if ($status['ready']) {
            $this->info('Configuração mínima do Banco Inter preenchida.');
        } else {
            $this->warn('Configuração parcial. Pode permanecer assim até você obter as credenciais/certificado do Inter.');
        }

        return self::SUCCESS;
    }
}
