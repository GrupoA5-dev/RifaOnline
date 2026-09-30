<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
            <p class="font-semibold text-gray-950 dark:text-white">Personalização do A5 Rifas</p>
            <p class="mt-1">
                Logo, tamanho, favicon, cores e rodapé são aplicados ao site. O tamanho da logo também é usado no login e no painel Gestão.
            </p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Salvar identidade visual
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
