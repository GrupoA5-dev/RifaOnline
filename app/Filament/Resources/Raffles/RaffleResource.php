<?php

namespace App\Filament\Resources\Raffles;

use App\Enums\AllocationMode;
use App\Enums\RaffleStatus;
use App\Filament\Resources\Raffles\Pages\CreateRaffle;
use App\Filament\Resources\Raffles\Pages\EditRaffle;
use App\Filament\Resources\Raffles\Pages\ListRaffles;
use App\Models\Raffle;
use App\Models\RaffleWinner;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class RaffleResource extends Resource
{
    protected static ?string $model = Raffle::class;

    protected static ?string $modelLabel = 'campanha';
    protected static ?string $pluralModelLabel = 'campanhas';
    protected static ?string $navigationLabel = 'Campanhas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('status')
                ->label('Status')
                ->options(collect(RaffleStatus::cases())->mapWithKeys(
                    fn (RaffleStatus $status) => [$status->value => $status->label()],
                )->all())
                ->default(RaffleStatus::Draft->value)
                ->required(),
            Select::make('allocation_mode')
                ->label('Distribuição dos números')
                ->options(collect(AllocationMode::cases())->mapWithKeys(
                    fn (AllocationMode $mode) => [$mode->value => $mode->label()],
                )->all())
                ->default(AllocationMode::Random->value)
                ->required(),
            TextInput::make('ticket_price')
                ->label('Preço por número (R$)')
                ->required()
                ->placeholder('1,00'),
            TextInput::make('revenue_goal')
                ->label('Meta de receita (R$)')
                ->placeholder('Ex.: 10000,00')
                ->helperText('Opcional. Usada no Dashboard para acompanhar o progresso financeiro da campanha.'),
            TextInput::make('total_numbers')
                ->label('Quantidade total de números')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('sales_goal_numbers')
                ->label('Meta de números vendidos')
                ->numeric()
                ->minValue(1)
                ->lte('total_numbers')
                ->helperText('Opcional. Se vazio, o Dashboard usa a quantidade total da campanha como referência.'),
            TextInput::make('number_digits')
                ->label('Dígitos do número')
                ->numeric()
                ->minValue(1)
                ->maxValue(12)
                ->default(6)
                ->required(),
            TextInput::make('min_purchase')
                ->label('Compra mínima')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
            TextInput::make('max_purchase')
                ->label('Compra máxima')
                ->numeric()
                ->minValue(1),
            TextInput::make('reservation_minutes')
                ->label('Tempo de reserva (minutos)')
                ->numeric()
                ->minValue(1)
                ->maxValue(1440)
                ->default(15)
                ->required(),
            DateTimePicker::make('starts_at')
                ->label('Início'),
            DateTimePicker::make('ends_at')
                ->label('Encerramento'),
            TextInput::make('organizer_name')
                ->label('Organizador')
                ->maxLength(255),
            TextInput::make('draw_mode')
                ->label('Método do sorteio')
                ->default('manual')
                ->maxLength(40)
                ->required(),
            TextInput::make('draw_reference')
                ->label('Referência do sorteio')
                ->maxLength(255),
            TextInput::make('cover_image')
                ->label('Caminho/URL da capa')
                ->maxLength(255),
            Toggle::make('featured')
                ->label('Campanha em destaque')
                ->default(false),
            Toggle::make('collect_name')
                ->label('Solicitar nome completo')
                ->helperText('Quando desligado, o campo não aparece no checkout.')
                ->default(true),
            Toggle::make('collect_email')
                ->label('Solicitar e-mail')
                ->helperText('Quando desligado, o campo não aparece no checkout.')
                ->default(true),
            Toggle::make('collect_phone')
                ->label('WhatsApp / telefone obrigatório')
                ->helperText('Usado como chave única do cadastro e para o participante consultar seus números posteriormente.')
                ->default(true)
                ->disabled()
                ->dehydrated(),
            Toggle::make('collect_document')
                ->label('Solicitar CPF')
                ->helperText('Quando desligado, o CPF não é solicitado nem validado.')
                ->default(true),
            Textarea::make('description')
                ->label('Descrição')
                ->rows(6),
            Textarea::make('rules')
                ->label('Regulamento')
                ->rows(8),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Campanha')->searchable()->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof RaffleStatus ? $state->label() : (string) $state),
                TextColumn::make('ticket_price')->label('Preço')->prefix('R$ '),
                TextColumn::make('total_numbers')->label('Números')->numeric()->sortable(),
                TextColumn::make('sales_goal_numbers')->label('Meta')->numeric()->placeholder('Total'),
                TextColumn::make('reservation_minutes')->label('Reserva')->suffix(' min'),
                IconColumn::make('featured')->label('Destaque')->boolean(),
                TextColumn::make('starts_at')->label('Início')->dateTime('d/m/Y H:i')->placeholder('Imediato'),
                TextColumn::make('ends_at')->label('Fim')->dateTime('d/m/Y H:i')->placeholder('Sem data'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('registerWinner')
                    ->label('Ganhador')
                    ->color('success')
                    ->schema([
                        Select::make('raffle_prize_id')
                            ->label('Prêmio')
                            ->options(fn (Raffle $record): array => $record->prizes()->orderBy('position')->get()->mapWithKeys(
                                fn ($prize) => [$prize->id => $prize->position.'º — '.$prize->title],
                            )->all())
                            ->placeholder('Sem prêmio vinculado'),
                        TextInput::make('ticket_number')
                            ->label('Número vencedor')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('O número precisa pertencer a um pedido pago desta campanha.'),
                        TextInput::make('winner_name')
                            ->label('Nome exibido')
                            ->maxLength(160)
                            ->helperText('Se ficar vazio, o nome do comprador será usado automaticamente.'),
                        DateTimePicker::make('announced_at')
                            ->label('Publicar em')
                            ->seconds(false)
                            ->default(now()),
                        Textarea::make('notes')
                            ->label('Observações internas')
                            ->rows(3),
                    ])
                    ->action(function (Raffle $record, array $data): void {
                        try {
                            RaffleWinner::create([
                                'raffle_id' => $record->getKey(),
                                'raffle_prize_id' => $data['raffle_prize_id'] ?? null,
                                'ticket_number' => (int) $data['ticket_number'],
                                'winner_name' => $data['winner_name'] ?? null,
                                'announced_at' => $data['announced_at'] ?? now(),
                                'notes' => $data['notes'] ?? null,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Ganhador registrado')
                                ->body('O resultado já pode aparecer na página pública da campanha.')
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Não foi possível registrar')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRaffles::route('/'),
            'create' => CreateRaffle::route('/create'),
            'edit' => EditRaffle::route('/{record}/edit'),
        ];
    }
}
