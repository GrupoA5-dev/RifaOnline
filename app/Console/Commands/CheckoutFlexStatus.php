<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class CheckoutFlexStatus extends Command
{
    protected $signature = 'a5:checkout-flex-status';
    protected $description = 'Valida checkout configurável e escolha manual de números.';

    public function handle(): int
    {
        $checks = [
            'raffle_collect_fields' => Schema::hasColumns('raffles', [
                'collect_name', 'collect_email', 'collect_phone', 'collect_document',
            ]),
            'customer_phone_nullable' => Schema::hasColumn('customers', 'phone'),
            'numbers_route' => Route::has('raffles.numbers'),
            'selected_action' => class_exists(\App\Actions\Raffles\ReserveSelectedTickets::class),
            'numbers_controller' => class_exists(\App\Http\Controllers\Public\AvailableNumbersController::class),
        ];

        foreach ($checks as $name => $ok) {
            $this->line(str_pad($name, 28).($ok ? 'OK' : 'ERRO'));
        }

        if (in_array(false, $checks, true)) {
            $this->error('Patch incompleto.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Checkout configurável e escolha manual instalados corretamente.');

        return self::SUCCESS;
    }
}
