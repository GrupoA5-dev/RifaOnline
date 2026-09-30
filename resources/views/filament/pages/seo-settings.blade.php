<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
            <p class="font-semibold text-gray-950 dark:text-white">SEO e prévia de compartilhamento</p>
            <p class="mt-1">As campanhas usam título, descrição e imagem próprios quando configurados. O charset permanece em UTF-8.</p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}
            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">Salvar SEO</x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
