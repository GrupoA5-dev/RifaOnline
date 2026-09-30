<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Services\Support\AuditService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class OperationsSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.operations-settings';
    protected static ?string $navigationLabel = 'Operação e segurança';
    protected static ?string $title = 'Operação e segurança';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 30;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill([
            'sales_enabled' => filter_var(SystemSetting::value('operations', 'sales_enabled', '1'), FILTER_VALIDATE_BOOL),
            'sales_pause_message' => SystemSetting::value('operations', 'sales_pause_message', 'As vendas estão temporariamente pausadas. Tente novamente em instantes.'),
            'admin_2fa_enabled' => filter_var(SystemSetting::value('security', 'admin_2fa_enabled', '1'), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('sales_enabled')
                ->label('Vendas habilitadas')
                ->helperText('Desative em uma emergência para impedir novas reservas sem tirar o painel do ar.'),
            Textarea::make('sales_pause_message')
                ->label('Mensagem quando as vendas estiverem pausadas')
                ->rows(3)
                ->maxLength(300),
            Toggle::make('admin_2fa_enabled')
                ->label('Autenticação em 2 fatores (2FA) no painel')
                ->helperText('Quando ativa, o login administrativo exige o código do aplicativo autenticador. Ao desativar, as configurações 2FA dos usuários são preservadas para uma futura reativação.')
                ->visible(fn (): bool => Auth::user()?->role === UserRole::SuperAdmin),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();

        SystemSetting::query()->updateOrCreate(
            ['group' => 'operations', 'key' => 'sales_enabled'],
            ['value' => !empty($data['sales_enabled']) ? '1' : '0', 'type' => 'boolean', 'is_public' => false],
        );
        SystemSetting::query()->updateOrCreate(
            ['group' => 'operations', 'key' => 'sales_pause_message'],
            ['value' => trim((string) ($data['sales_pause_message'] ?? '')), 'type' => 'string', 'is_public' => true],
        );

        $twoFactorEnabled = filter_var(
            SystemSetting::value('security', 'admin_2fa_enabled', '1'),
            FILTER_VALIDATE_BOOL,
        );

        if (Auth::user()?->role === UserRole::SuperAdmin) {
            $twoFactorEnabled = ! empty($data['admin_2fa_enabled']);

            SystemSetting::query()->updateOrCreate(
                ['group' => 'security', 'key' => 'admin_2fa_enabled'],
                ['value' => $twoFactorEnabled ? '1' : '0', 'type' => 'boolean', 'is_public' => false],
            );
        }

        AuditService::record('operations.settings.updated', after: [
            'sales_enabled' => !empty($data['sales_enabled']),
            'admin_2fa_enabled' => $twoFactorEnabled,
        ]);

        Notification::make()->success()->title('Configuração operacional atualizada')->send();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->active === true && in_array($user?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }
}
