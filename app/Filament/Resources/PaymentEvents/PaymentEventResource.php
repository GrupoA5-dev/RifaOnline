<?php

namespace App\Filament\Resources\PaymentEvents;

use App\Filament\Resources\PaymentEvents\Pages\ListPaymentEvents;
use App\Models\PaymentEvent;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentEventResource extends Resource
{
    protected static ?string $model = PaymentEvent::class;

    protected static ?string $modelLabel = 'evento de pagamento';
    protected static ?string $pluralModelLabel = 'eventos de pagamento';
    protected static ?string $navigationLabel = 'Webhooks de pagamento';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('event_type')->label('Evento')->searchable()->placeholder('—'),
                TextColumn::make('resource_id')->label('Recurso')->searchable()->placeholder('—'),
                IconColumn::make('signature_valid')->label('Assinatura')->boolean(),
                TextColumn::make('processed_at')->label('Processado em')->dateTime('d/m/Y H:i')->placeholder('Pendente')->sortable(),
                TextColumn::make('processing_error')->label('Erro')->limit(80)->placeholder('—'),
                TextColumn::make('created_at')->label('Recebido em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentEvents::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
