<?php

namespace App\Filament\Organizer\Resources\Raffles;

use App\Enums\RaffleStatus;
use App\Filament\Organizer\Resources\Raffles\Pages\ListRaffles;
use App\Models\Organizer;
use App\Models\Raffle;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant-only, read-only campaign listing.
 * Never reuse the central admin's unrestricted campaign resource here.
 */
class RaffleResource extends Resource
{
    protected static ?string $model = Raffle::class;

    protected static ?string $slug = 'campanhas';

    protected static ?string $modelLabel = 'campanha';

    protected static ?string $pluralModelLabel = 'campanhas';

    protected static ?string $navigationLabel = 'Campanhas';

    protected static bool $isScopedToTenant = true;

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getCurrentPanel()?->getId() === 'organizer'
            ? Filament::getTenant()
            : null;

        // Fail closed if there is no active organizer tenant.
        // NULL organizer_id (legacy campaigns) must never be visible here.
        return parent::getEloquentQuery()
            ->where(
                'raffles.organizer_id',
                $tenant instanceof Organizer && $tenant->status === 'active'
                    ? $tenant->getKey()
                    : 0,
            );
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label('Campanha')
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->label('Situação')
                ->badge()
                ->formatStateUsing(
                    fn ($state): string => $state instanceof RaffleStatus
                        ? $state->label()
                        : (string) $state,
                ),
            TextColumn::make('total_numbers')
                ->label('Números')
                ->numeric(),
            TextColumn::make('ticket_price')
                ->label('Preço por número')
                ->prefix('R$ '),
            TextColumn::make('created_at')
                ->label('Criada em')
                ->dateTime('d/m/Y H:i'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRaffles::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return false;
    }
}
