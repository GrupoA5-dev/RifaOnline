<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Raffle;
use App\Models\TicketAllocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Work2Status extends Command
{
    protected $signature = 'a5:work2-status';

    protected $description = 'Verifica a instalação do motor de rifas do WORK 2.';

    public function handle(): int
    {
        $tables = [
            'raffles',
            'raffle_prizes',
            'customers',
            'orders',
            'order_tickets',
            'ticket_allocations',
        ];

        $missing = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if ($missing !== []) {
            $this->error('Tabelas ausentes: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $this->table(['Item', 'Total'], [
            ['Rifas', Raffle::query()->count()],
            ['Clientes', Customer::query()->count()],
            ['Pedidos', Order::query()->count()],
            ['Alocações ativas/pagas', TicketAllocation::query()->count()],
        ]);

        $this->info('WORK 2 instalado corretamente.');

        return self::SUCCESS;
    }
}
