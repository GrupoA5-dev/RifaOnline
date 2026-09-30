<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ResetOperationalData extends Command
{
    protected $signature = 'a5:reset-operational-data
                            {--confirm= : Digite ZERAR para executar a exclusão definitiva}';

    protected $description = 'Zera compras, pagamentos, clientes, ganhadores e analytics; mantém apenas campanhas ativas e suas configurações.';

    /**
     * Tabelas operacionais que devem terminar vazias.
     * A ordem também é segura para exclusão respeitando as FKs conhecidas.
     */
    private array $operationalTables = [
        'payment_events',
        'payments',
        'raffle_winners',
        'ticket_allocations',
        'order_tickets',
        'orders',
        'customers',
        'raffle_events',
        'audit_logs',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->info('A5 — Reset dos dados operacionais');
        $this->line('Este comando NÃO apaga usuários administrativos, configurações, branding, integração Pix ou campanhas ativas.');
        $this->newLine();

        $activeRaffles = Schema::hasTable('raffles')
            ? DB::table('raffles')->where('status', 'active')->orderBy('id')->get(['id', 'title', 'slug'])
            : collect();

        $inactiveCount = Schema::hasTable('raffles')
            ? DB::table('raffles')->where('status', '!=', 'active')->count()
            : 0;

        $preview = [];
        foreach ($this->operationalTables as $table) {
            if (Schema::hasTable($table)) {
                $preview[] = [$table, DB::table($table)->count()];
            }
        }

        if (Schema::hasTable('coupons') && Schema::hasTable('raffles')) {
            $inactiveIds = DB::table('raffles')->where('status', '!=', 'active')->pluck('id');
            $preview[] = ['coupons de campanhas não ativas', $inactiveIds->isEmpty() ? 0 : DB::table('coupons')->whereIn('raffle_id', $inactiveIds)->count()];
        }

        $preview[] = ['campanhas não ativas', $inactiveCount];

        $this->table(['Dado que será removido', 'Quantidade'], $preview);

        $this->newLine();
        $this->info('Campanhas que serão preservadas:');
        if ($activeRaffles->isEmpty()) {
            $this->warn('Nenhuma campanha com status ACTIVE foi encontrada.');
        } else {
            $this->table(
                ['ID', 'Campanha', 'Slug'],
                $activeRaffles->map(fn ($raffle) => [$raffle->id, $raffle->title, $raffle->slug])->all()
            );
        }

        if ($this->option('confirm') !== 'ZERAR') {
            $this->newLine();
            $this->warn('MODO DE VISUALIZAÇÃO: nenhum dado foi excluído.');
            $this->line('Para executar de verdade, rode novamente com: --confirm=ZERAR');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('Executando exclusão definitiva...');

        try {
            DB::transaction(function (): void {
                // Guardamos IDs antes de excluir as campanhas não ativas.
                $inactiveRaffleIds = Schema::hasTable('raffles')
                    ? DB::table('raffles')->where('status', '!=', 'active')->pluck('id')
                    : collect();

                // Cupons de campanhas removidas não devem virar cupons globais pelo nullOnDelete.
                if (Schema::hasTable('coupons') && $inactiveRaffleIds->isNotEmpty()) {
                    DB::table('coupons')->whereIn('raffle_id', $inactiveRaffleIds)->delete();
                }

                // Dados operacionais e históricos de testes.
                foreach ($this->operationalTables as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                // Agora removemos todas as campanhas que não estão explicitamente ativas.
                // Prêmios e descontos por quantidade vinculados a elas são removidos por cascade.
                if (Schema::hasTable('raffles')) {
                    DB::table('raffles')->where('status', '!=', 'active')->delete();
                }
            }, 3);

            // Como as tabelas abaixo ficaram vazias, reiniciamos seus IDs para 1.
            // Não alteramos AUTO_INCREMENT de raffles porque campanhas ativas foram preservadas.
            foreach ($this->operationalTables as $table) {
                if (Schema::hasTable($table) && DB::table($table)->count() === 0) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                    } catch (Throwable) {
                        // O reset dos dados já foi concluído; falha em AUTO_INCREMENT não deve abortar o processo.
                    }
                }
            }

            $this->newLine();
            $this->info('RESET CONCLUÍDO COM SUCESSO.');
            $this->line('Compras/pedidos: 0');
            $this->line('Pagamentos/eventos Pix: 0');
            $this->line('Clientes: 0');
            $this->line('Números reservados/vendidos: 0');
            $this->line('Ganhadores: 0');
            $this->line('Analytics/auditoria anteriores: 0');
            $this->line('Campanhas ativas preservadas: '.(Schema::hasTable('raffles') ? DB::table('raffles')->where('status', 'active')->count() : 0));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('O reset NÃO foi concluído. A transação foi revertida.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
