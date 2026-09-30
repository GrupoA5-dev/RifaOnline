<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class Work4Status extends Command
{
    protected $signature = 'a5:work4-status';
    protected $description = 'Valida as rotas e os assets principais do WORK 4.';

    public function handle(): int
    {
        $manifestPath = public_path('build/manifest.json');
        $manifest = is_file($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : null;

        $checks = [
            'home_route' => Route::has('home'),
            'raffle_route' => Route::has('raffles.show'),
            'reserve_route' => Route::has('raffles.reserve'),
            'checkout_route' => Route::has('checkout.show'),
            'pix_route' => Route::has('checkout.orders.pix'),
            'payment_status_route' => Route::has('checkout.orders.payment-status'),
            'home_vue' => is_file(resource_path('js/Pages/Home.vue')),
            'raffle_vue' => is_file(resource_path('js/Pages/Raffles/Show.vue')),
            'pix_vue' => is_file(resource_path('js/Pages/Checkout/Pix.vue')),
            'vite_manifest' => is_array($manifest) && isset($manifest['resources/js/app.ts']),
        ];

        foreach ($checks as $label => $ok) {
            $this->line(str_pad($label, 24).($ok ? 'OK' : 'ERRO'));
        }

        if (in_array(false, $checks, true)) {
            $this->newLine();
            $this->error('WORK 4 incompleto. Revise os itens marcados como ERRO.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('WORK 4 instalado corretamente.');
        return self::SUCCESS;
    }
}
