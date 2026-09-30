<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $modelLabel = 'registro de auditoria';
    protected static ?string $pluralModelLabel = 'auditoria';
    protected static ?string $navigationLabel = 'Auditoria';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->placeholder('Sistema / público')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Ação')
                    ->badge()
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->label('Entidade')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->searchable(),
                TextColumn::make('entity_id')
                    ->label('ID')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->limit(55)
                    ->tooltip(fn (?string $state): ?string => $state),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
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
}
