<?php

namespace App\Console\Commands;

use App\Services\Payments\StaticPixPayload;
use Illuminate\Console\Command;

class PixStaticStatus extends Command
{
    protected $signature = 'a5:pix-static-status';

    protected $description = 'Valida a configuração do Pix estático e a geração do BR Code.';

    public function handle(StaticPixPayload $payload): int
    {
        $checks = [
            'mode' => config('pix.mode') === 'static',
            'pix_key' => filled(config('pix.key')),
            'merchant_name' => filled(config('pix.merchant_name')),
            'merchant_city' => filled(config('pix.merchant_city')),
        ];

        foreach ($checks as $label => $ok) {
            $this->line(str_pad($label, 22).($ok ? 'OK' : 'FALTA'));
        }

        if (in_array(false, $checks, true)) {
            $this->error('Configure o Pix estático no .env antes de prosseguir.');
            return self::FAILURE;
        }

        try {
            $code = $payload->make(
                key: (string) config('pix.key'),
                merchantName: (string) config('pix.merchant_name'),
                merchantCity: (string) config('pix.merchant_city'),
                amountCents: 100,
                txid: 'A5TESTE123',
            );

            $this->line(str_pad('br_code', 22).(str_starts_with($code, '000201') && str_contains($code, 'br.gov.bcb.pix') ? 'OK' : 'ERRO'));
        } catch (\Throwable $e) {
            $this->error('Falha ao gerar BR Code: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Pix estático configurado corretamente.');
        $this->warn('A confirmação é manual. O QR Code estático não expira tecnicamente no banco do pagador; oriente o cliente a não pagar após o prazo da reserva.');

        return self::SUCCESS;
    }
}
