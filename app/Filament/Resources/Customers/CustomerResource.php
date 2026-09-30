<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Services\Support\AuditService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $modelLabel = 'cliente';
    protected static ?string $pluralModelLabel = 'clientes';
    protected static ?string $navigationLabel = 'Clientes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(120),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->maxLength(190),
            TextInput::make('phone')
                ->label('Telefone / WhatsApp')
                ->tel()
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            TextInput::make('document_type')
                ->label('Tipo do documento')
                ->maxLength(10)
                ->placeholder('CPF'),
            TextInput::make('document_number')
                ->label('Documento')
                ->maxLength(32),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('phone')->label('Telefone')->searchable()->placeholder('—'),
                TextColumn::make('email')->label('E-mail')->searchable()->placeholder('—'),
                TextColumn::make('document_number')->label('Documento')->searchable()->placeholder('—'),
                TextColumn::make('orders_count')->label('Pedidos')->counts('orders')->sortable(),
                TextColumn::make('created_at')->label('Cadastro')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                Action::make('resetAccessPin')
                    ->label('Novo código de acesso')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Gerar novo código de acesso?')
                    ->modalDescription('O código antigo deixará de funcionar imediatamente. Informe o novo código ao cliente por um canal seguro.')
                    ->action(function (Customer $record): void {
                        $pin = $record->rotateAccessPin();

                        AuditService::record('customer.access_pin.rotated', $record, after: [
                            'access_pin' => '[REDACTED]',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Novo código gerado')
                            ->body('Código do cliente: '.$pin)
                            ->send();
                    }),
                Action::make('deleteCustomer')
                    ->label('Excluir')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Excluir este cliente?')
                    ->modalDescription('A exclusão só é permitida quando o cliente não possui pedidos. Clientes com histórico financeiro devem ser editados, não apagados.')
                    ->visible(fn (Customer $record): bool => ! $record->orders()->exists())
                    ->action(function (Customer $record): void {
                        try {
                            AuditService::record('customer.deleted', $record, before: [
                                'name' => $record->name,
                                'phone' => $record->phone,
                                'email' => $record->email,
                            ]);

                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Cliente excluído')
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Não foi possível excluir')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Customer && ! $record->orders()->exists();
    }
}
