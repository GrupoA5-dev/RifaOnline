<?php

namespace App\Filament\Organizer\Resources\Raffles\Pages;

use App\Filament\Organizer\Resources\Raffles\RaffleResource;
use App\Enums\RaffleStatus;
use App\Models\Organizer;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Resources\Pages\ListRecords;

class ListRaffles extends ListRecords
{
    protected static string $resource = RaffleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createDraft')
                ->label('Criar rascunho')
                ->modalSubmitActionLabel('Salvar rascunho')
                ->visible(fn (): bool => $this->masterOrganizer() !== null)
                ->schema([
                    TextInput::make('title')->label('Nome da campanha')->required()->maxLength(160),
                    TextInput::make('total_numbers')->label('Quantidade de números')
                        ->required()->integer()->minValue(1)->maxValue(1000000),
                    TextInput::make('ticket_price')->label('Preço por número')->prefix('R
)
                        ->placeholder('1,00')->required()
                        ->regex('/^(?!0+(?:[,.]0{1,2})?$)\d{1,7}(?:[,.]\d{1,2})?$/D'),
                ])
                ->action(function (array $data): void {
                    $organizer = $this->masterOrganizer();
                    abort_unless($organizer, 403);

                    $organizer->raffles()->create([
                        'title' => $data['title'],
                        'slug' => Str::uuid()->toString(),
                        'total_numbers' => $data['total_numbers'],
                        'ticket_price' => $data['ticket_price'],
                        'status' => RaffleStatus::Draft,
                    ]);

                    Notification::make()->success()->title('Rascunho criado')->send();
                }),
        ];
    }

    private function masterOrganizer(): ?Organizer
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();
        if (! $tenant instanceof Organizer || ! $user instanceof User || ! $user->active) {
            return null;
        }

        return $user->organizers()->whereKey($tenant->getKey())
            ->where('organizers.status', 'active')->wherePivot('role', 'master')->first();
    }
}
