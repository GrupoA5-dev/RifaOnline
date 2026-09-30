<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class Work6Status extends Command
{
    protected $signature = 'a5:work6-status';
    protected $description = 'Valida os recursos do WORK 6.';

    public function handle(): int
    {
        $checks = [
            'customer_public_token' => Schema::hasColumn('customers', 'public_token'),
            'raffle_winners' => Schema::hasTable('raffle_winners'),
            'my_numbers_lookup_route' => Route::has('my-numbers.lookup'),
            'my_numbers_show_route' => Route::has('my-numbers.show'),
            'winner_resource' => class_exists(\App\Filament\Resources\RaffleWinners\RaffleWinnerResource::class),
            'customer_edit_page' => class_exists(\App\Filament\Resources\Customers\Pages\EditCustomer::class),
        ];

        foreach ($checks as $label => $ok) {
            $this->line(str_pad($label, 30).($ok ? '<fg=green>OK</>' : '<fg=red>FAIL</>'));
        }

        if (in_array(false, $checks, true)) {
            $this->newLine();
            $this->error('WORK 6 incompleto.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('WORK 6 instalado corretamente.');
        return self::SUCCESS;
    }
}
