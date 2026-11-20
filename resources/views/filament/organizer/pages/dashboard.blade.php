<x-filament-panels::page>
    @php
        $organizer = \Filament\Facades\Filament::getTenant();
    @endphp

    <div class="space-y-4">
        <h2 class="text-xl font-semibold">
            Bem-vindo à {{ $organizer->name }}
        </h2>

        <p>
            Seu acesso à organização está funcionando com autenticação em duas etapas.
        </p>

        <p>
            As áreas de campanhas, pedidos, participantes e pagamentos ainda estão em homologação
            e serão liberadas somente após os testes de isolamento entre organizadores.
        </p>
    </div>
</x-filament-panels::page>
