<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'usuário';
    protected static ?string $pluralModelLabel = 'usuários';
    protected static ?string $navigationLabel = 'Usuários';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('role')
                ->label('Perfil')
                ->options(collect(UserRole::cases())->mapWithKeys(
                    fn (UserRole $role) => [$role->value => $role->label()],
                )->all())
                ->required(),
            Toggle::make('active')
                ->label('Ativo')
                ->default(true)
                ->required(),
            TextInput::make('password')
                ->label('Senha')
                ->password()
                ->revealable()
                ->rules([
                    Password::min(12)->mixedCase()->numbers()->symbols(),
                ])
                ->helperText('Mínimo de 12 caracteres, com maiúsculas, minúsculas, número e símbolo.')
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')->label('Perfil')->badge()->formatStateUsing(fn ($state) => $state instanceof UserRole ? $state->label() : (string) $state),
                IconColumn::make('active')->label('Ativo')->boolean(),
                IconColumn::make('app_authentication_secret')
                    ->label('2FA')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => filled($record->getAppAuthenticationSecret())),
                TextColumn::make('last_login_at')->label('Último login')->dateTime('d/m/Y H:i')->placeholder('Nunca'),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
