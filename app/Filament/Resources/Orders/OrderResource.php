<?php

namespace App\Filament\Resources\Orders;

use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\ConfirmExpiredOrderPayment;
use App\Services\Support\AuditService;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $modelLabel = 'pedido';
    protected static ?string $pluralModelLabel = 'pedidos';
    protected static ?string $navigationLabel = 'Pedidos';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('id')
                ->label('Pedido')
                ->formatStateUsing(fn ($state): string => '#'.(string) $state),
            TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : (string) $state),
            TextEntry::make('raffle.title')->label('Campanha'),
            TextEntry::make('customer.name')->label('Cliente')->placeholder('—'),
            TextEntry::make('customer.email')->label('E-mail')->placeholder('—'),
            TextEntry::make('customer.phone')->label('Telefone')->placeholder('—'),
            TextEntry::make('quantity')->label('Quantidade'),
            TextEntry::make('total_cents')
                ->label('Total')
                ->formatStateUsing(fn ($state): string => 'R$ '.Money::format((int) $state)),
            TextEntry::make('numbers')
                ->label('Números')
                ->state(function (Order $record): string {
                    $digits = max(1, (int) ($record->raffle?->number_digits ?? 1));

                    return $record->tickets()
                        ->orderBy('number')
                        ->pluck('number')
                        ->map(fn ($number): string => str_pad((string) $number, $digits, '0', STR_PAD_LEFT))
                        ->join(', ') ?: '—';
                })
                ->columnSpanFull(),
            TextEntry::make('payment_status')
                ->label('Pagamento')
                ->state(function (Order $record): string {
                    $payment = $record->payments()->latest('id')->first();

                    return $payment?->status?->label() ?? 'Sem pagamento';
                }),
            TextEntry::make('payment_txid')
                ->label('TXID / Referência')
                ->state(fn (Order $record): string => (string) ($record->payments()->latest('id')->value('external_reference') ?: '—')),
            TextEntry::make('expires_at')->label('Expira em')->dateTime('d/m/Y H:i')->placeholder('—'),
            TextEntry::make('paid_at')->label('Pago em')->dateTime('d/m/Y H:i')->placeholder('—'),
            TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('raffle.title')->label('Campanha')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Cliente')->searchable(),
                TextColumn::make('customer.phone')->label('Telefone')->searchable()->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : (string) $state),
                TextColumn::make('quantity')->label('Qtd.')->numeric()->sortable(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'R$ '.Money::format((int) $state)),
                TextColumn::make('expires_at')->label('Expira em')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('paid_at')->label('Pago em')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Abrir')
                    ->modalHeading(fn (Order $record): string => 'Pedido #'.$record->id),
Action::make('confirmExpiredPayment')
    ->label('Confirmar pagamento após expiração')
    ->color('warning')
    ->icon('heroicon-o-exclamation-triangle')
    ->requiresConfirmation()
    ->modalHeading('Confirmar pagamento após expiração?')
    ->modalDescription('O pedido venceu o prazo de reserva, mas existe pagamento aprovado. Esta ação confirmará o pedido e os números 
reservados.')
    ->visible(function (Order $record): bool {
        $paymentApproved = $record->payments()
            ->where('status', \App\Enums\PaymentStatus::Approved->value)
            ->exists();

        return $record->status === OrderStatus::AwaitingPayment
            && $record->expires_at?->isPast()
            && $paymentApproved;
    })
    ->action(function (Order $record): void {
        try {
            $updated = app(ConfirmExpiredOrderPayment::class)->handle($record);

            Notification::make()
                ->success()
                ->title('Pagamento confirmado')
                ->body('Pedido e números atualizados após expiração.')
                ->send();

        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Não foi possível confirmar')
                ->body($e->getMessage())
                ->send();
        }
    }),
Action::make('confirmPaymentManual')
    ->label('Confirmar pagamento manual')
    ->color('success')
    ->icon('heroicon-o-check-circle')
    ->requiresConfirmation()
    ->modalHeading('Confirmar pagamento deste pedido?')
    ->modalDescription('Esta ação marcará o pedido como pago e confirmará os números reservados.')
    ->visible(fn (Order $record): bool => $record->status === OrderStatus::AwaitingPayment)
    ->action(function (Order $record): void {
        try {
            $before = [
                'status' => $record->status->value,
                'paid_at' => $record->paid_at?->toIso8601String(),
            ];

            $updated = app(MarkOrderPaid::class)->handle($record, now());

            AuditService::record('order.payment_confirmed_manual', $updated, before: $before, after: [
                'status' => $updated->status->value,
                'paid_at' => $updated->paid_at?->toIso8601String(),
            ]);

            Notification::make()
                ->success()
                ->title('Pagamento confirmado')
                ->body('Pedido e números atualizados com sucesso.')
                ->send();

        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Não foi possível confirmar')
                ->body($e->getMessage())
                ->send();
        }
    }),
                Action::make('deleteOrder')
                    ->label('Excluir')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Excluir este pedido?')
                    ->modalDescription('Pedidos pagos, reembolsados ou em chargeback são protegidos e não podem ser apagados. Para pedidos não financeiros, a exclusão remove o pedido, pagamentos pendentes e libera seus números.')
                    ->visible(fn (Order $record): bool => in_array($record->status, [
                        OrderStatus::AwaitingPayment,
                        OrderStatus::Expired,
                        OrderStatus::Cancelled,
                    ], true))
                    ->action(function (Order $record): void {
                        try {
                            DB::transaction(function () use ($record): void {
                                $record->payments()->delete();
                                $record->delete();
                            });

                            Notification::make()
                                ->success()
                                ->title('Pedido excluído')
                                ->body('O pedido foi removido e os números associados foram liberados.')
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Não foi possível excluir')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
