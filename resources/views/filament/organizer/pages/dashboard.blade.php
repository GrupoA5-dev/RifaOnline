<x-filament-panels::page>
    @php
        $organizer = \Filament\Facades\Filament::getTenant();
        $metrics = app(\App\Services\Organizers\OrganizerDashboardMetrics::class)->for($organizer);
    @endphp

    <div class="space-y-4">
        <h2 class="text-xl font-semibold">
            Bem-vindo à {{ $organizer->name }}
        </h2>

        <p>
            Seu acesso à organização está funcionando com autenticação em duas etapas.
        </p>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Campanhas</p>
                <p class="text-2xl font-semibold">{{ $metrics['campaigns'] }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pedidos</p>
                <p class="text-2xl font-semibold">{{ $metrics['orders'] }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Participantes com pedidos</p>
                <p class="text-2xl font-semibold">{{ $metrics['participants'] }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pagamentos registrados</p>
                <p class="text-2xl font-semibold">{{ $metrics['payments'] }}</p>
            </x-filament::section>
        </div>

        <p>
            Os indicadores acima são somente de leitura e pertencem à organização selecionada.
            As listas de clientes e as ações de campanhas, pedidos e pagamentos continuam bloqueadas
            até concluirmos a revisão do isolamento multi-tenant.
        </p>
    </div>
</x-filament-panels::page>
