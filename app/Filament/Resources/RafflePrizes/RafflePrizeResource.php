<?php

namespace App\Filament\Resources\RafflePrizes;

use App\Filament\Resources\RafflePrizes\Pages\CreateRafflePrize;
use App\Filament\Resources\RafflePrizes\Pages\EditRafflePrize;
use App\Filament\Resources\RafflePrizes\Pages\ListRafflePrizes;
use App\Models\Raffle;
use App\Models\RafflePrize;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RafflePrizeResource extends Resource
{
    protected static ?string $model = RafflePrize::class;

    protected static ?string $modelLabel = 'prêmio';
    protected static ?string $pluralModelLabel = 'prêmios';
    protected static ?string $navigationLabel = 'Prêmios';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('raffle_id')
                ->label('Campanha')
                ->options(fn () => Raffle::query()->orderBy('title')->pluck('title', 'id')->all())
                ->searchable()
                ->required(),
            TextInput::make('position')
                ->label('Posição')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('title')
                ->label('Prêmio')
                ->required()
                ->maxLength(255),
            TextInput::make('prize_value')
                ->label('Valor estimado (R$)')
                ->placeholder('0,00'),
            TextInput::make('image')
                ->label('Caminho/URL da imagem')
                ->maxLength(255),
            Textarea::make('description')
                ->label('Descrição')
                ->rows(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('raffle.title')->label('Campanha')->searchable()->sortable(),
                TextColumn::make('position')->label('Posição')->sortable(),
                TextColumn::make('title')->label('Prêmio')->searchable(),
                TextColumn::make('prize_value')->label('Valor')->prefix('R$ ')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRafflePrizes::route('/'),
            'create' => CreateRafflePrize::route('/create'),
            'edit' => EditRafflePrize::route('/{record}/edit'),
        ];
    }
}
