<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
            <p class="font-semibold text-gray-950 dark:text-white">Notificações administrativas por e-mail</p>
            <p class="mt-1">Configure um SMTP próprio. A senha não é exibida novamente e fica criptografada com a APP_KEY. Salve antes de usar o botão de teste.</p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}
            <div class="flex flex-wrap justify-end gap-3">
                <x-filament::button type="button" color="gray" wire:click="sendTest" icon="heroicon-o-paper-airplane">Enviar teste</x-filament::button>
                <x-filament::button type="submit" icon="heroicon-o-check">Salvar e-mail</x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
