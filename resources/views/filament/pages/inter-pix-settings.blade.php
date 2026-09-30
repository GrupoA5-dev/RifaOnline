<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
            <p class="font-semibold text-gray-950 dark:text-white">Configuração preparada para o Banco Inter</p>
            <p class="mt-1">
                Você pode deixar a integração desativada agora e preencher as credenciais quando o Inter liberar sua integração.
                Client Secret e senha de certificado são armazenados criptografados. Certificados ficam no storage privado do Laravel e não no diretório público.
            </p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Salvar configuração
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
