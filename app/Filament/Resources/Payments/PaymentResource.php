<?php

namespace App\Filament\Resources\Payments;

use App\Actions\Payments\ConfirmStaticPixPayment;
use App\Actions\Payments\ReleaseStaticPixReservation;
use App\Actions\Payments\ReconcileInterPixPayment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use App\Support\Money;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $modelLabel = 'pagamento';
    protected static ?string $pluralModelLabel = 'pagamentos';
    protected static ?string $navigationLabel = 'Pagamentos';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('pix_confirmation')
                    ->label('Confirmar Pix')
                    ->state(function (Payment $record): string {
                        if ($record->status === PaymentStatus::Approved) {
                            return 'Pago';
                        }

                        if ($record->gateway === 'pix_static'
                            && $record->status === PaymentStatus::Pending
                            && $record->order?->status === OrderStatus::AwaitingPayment) {
                            return 'Confirmar';
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(fn (Payment $record): string => $record->status === PaymentStatus::Approved
                        ? 'success'
                        : (($record->gateway === 'pix_static' && $record->status === PaymentStatus::Pending) ? 'warning' : 'gray'))
                    ->action(
                        Action::make('confirmPixInline')
                            ->label('Confirmar Pix')
                            ->color('success')
                            ->schema([
                                DateTimePicker::make('paid_at')
                                    ->label('Data/hora exibida no extrato')
                                    ->seconds(false)
                                    ->default(now())
                                    ->required(),
                            ])
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar recebimento Pix?')
                            ->modalDescription('Confira o crédito, valor e TXID no banco. Informe a data/hora real do recebimento. O pedido e os números serão marcados como pagos.')
                            ->disabled(fn (Payment $record): bool => ! (
                                $record->gateway === 'pix_static'
                                && $record->status === PaymentStatus::Pending
                                && $record->order?->status === OrderStatus::AwaitingPayment
                            ))
                            ->action(function (Payment $record, array $data): void {
                                try {
                                    app(ConfirmStaticPixPayment::class)->handle($record, Carbon::parse($data['paid_at']));

                                    Notification::make()
                                        ->success()
                                        ->title('Pix confirmado')
                                        ->body('O pedido e os números foram marcados como pagos.')
                                        ->send();
                                } catch (Throwable $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Não foi possível confirmar')
                                        ->body($e->getMessage())
                                        ->send();
                                }
                            }),
                    ),
                TextColumn::make('order_id')->label('Pedido')->sortable(),
                TextColumn::make('order.customer.name')->label('Cliente')->searchable(),
                TextColumn::make('order.raffle.title')->label('Campanha')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : (string) $state),
                TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state): string => 'R$ '.Money::format((int) $state)),
                TextColumn::make('gateway')
                    ->label('Modo')
                    ->formatStateUsing(fn ($state): string => match ((string) $state) {
                        'pix_static' => 'Pix estático',
                        'inter_pix' => 'Pix Banco Inter',
                        default => (string) $state,
                    }),
                TextColumn::make('external_reference')->label('TXID')->searchable()->placeholder('—'),
                TextColumn::make('status_detail')->label('Detalhe')->placeholder('—'),
                TextColumn::make('expires_at')->label('Prazo')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('paid_at')->label('Pago em')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('reconcileInterPix')
                    ->label('Consultar Inter')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Payment $record): bool => $record->gateway === 'inter_pix'
                        && in_array($record->status, [PaymentStatus::Creating, PaymentStatus::Pending, PaymentStatus::InProcess], true))
                    ->action(function (Payment $record): void {
                        try {
                            $updated = app(ReconcileInterPixPayment::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title('Consulta concluída')
                                ->body('Status atual: '.$updated->status->label())
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Falha ao consultar Banco Inter')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('releaseStaticPix')
                    ->label('Liberar reserva')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Liberar números desta reserva?')
                    ->modalDescription('Faça isso somente depois de conferir no banco que o Pix NÃO foi recebido. Os números voltarão a ficar disponíveis.')
                    ->visible(fn (Payment $record): bool => $record->gateway === 'pix_static'
                        && $record->status === PaymentStatus::Pending
                        && $record->order?->status === OrderStatus::AwaitingPayment
                        && (bool) $record->order?->expires_at?->isPast())
                    ->action(function (Payment $record): void {
                        try {
                            app(ReleaseStaticPixReservation::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title('Reserva liberada')
                                ->body('Os números foram devolvidos à campanha.')
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Não foi possível liberar')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
