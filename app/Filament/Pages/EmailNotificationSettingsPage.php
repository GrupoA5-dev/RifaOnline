<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Services\Notifications\EmailNotificationService;
use App\Services\Support\AuditService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class EmailNotificationSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.email-notification-settings';

    protected static ?string $navigationLabel = 'Notificações por e-mail';
    protected static ?string $title = 'Notificações por e-mail';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 30;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'email_enabled' => $this->bool('email_enabled'),
            'notify_new_order' => $this->bool('notify_new_order'),
            'notify_payment_confirmed' => $this->bool('notify_payment_confirmed'),
            'recipient_email' => SystemSetting::value('notifications', 'recipient_email', Auth::user()?->email),
            'smtp_host' => SystemSetting::value('notifications', 'smtp_host', ''),
            'smtp_port' => (int) SystemSetting::value('notifications', 'smtp_port', 587),
            'smtp_username' => SystemSetting::value('notifications', 'smtp_username', ''),
            'smtp_password' => '',
            'smtp_encryption' => SystemSetting::value('notifications', 'smtp_encryption', 'tls'),
            'from_address' => SystemSetting::value('notifications', 'from_address', Auth::user()?->email),
            'from_name' => SystemSetting::value('notifications', 'from_name', SystemSetting::value('general', 'system_name', config('app.name'))),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('email_enabled')
                ->label('Ativar notificações por e-mail')
                ->helperText('Quando desligado, o sistema não tenta enviar notificações administrativas.'),
            Toggle::make('notify_new_order')
                ->label('Avisar quando houver novo pedido'),
            Toggle::make('notify_payment_confirmed')
                ->label('Avisar quando o pagamento for confirmado'),
            TextInput::make('recipient_email')
                ->label('E-mail que recebe os avisos')
                ->email()
                ->maxLength(190)
                ->helperText('Por padrão pode ser o mesmo e-mail do usuário administrador.'),
            TextInput::make('smtp_host')
                ->label('Servidor SMTP')
                ->placeholder('smtp.exemplo.com')
                ->maxLength(190),
            TextInput::make('smtp_port')
                ->label('Porta SMTP')
                ->numeric()
                ->minValue(1)
                ->maxValue(65535)
                ->default(587),
            TextInput::make('smtp_username')
                ->label('Usuário SMTP')
                ->maxLength(190),
            TextInput::make('smtp_password')
                ->label('Senha SMTP')
                ->password()
                ->revealable()
                ->helperText('Deixe vazio para manter a senha já salva. A senha é armazenada criptografada.'),
            Select::make('smtp_encryption')
                ->label('Segurança')
                ->options([
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                    'none' => 'Sem criptografia',
                ])
                ->default('tls'),
            TextInput::make('from_address')
                ->label('E-mail remetente')
                ->email()
                ->maxLength(190),
            TextInput::make('from_name')
                ->label('Nome do remetente')
                ->maxLength(120),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        if (($data['email_enabled'] ?? false) && blank($data['recipient_email'] ?? null)) {
            Notification::make()->danger()->title('Informe o e-mail que receberá as notificações.')->send();
            return;
        }

        $this->put('notifications', 'email_enabled', ($data['email_enabled'] ?? false) ? '1' : '0', 'boolean', false);
        $this->put('notifications', 'notify_new_order', ($data['notify_new_order'] ?? false) ? '1' : '0', 'boolean', false);
        $this->put('notifications', 'notify_payment_confirmed', ($data['notify_payment_confirmed'] ?? false) ? '1' : '0', 'boolean', false);
        $this->put('notifications', 'recipient_email', trim((string) ($data['recipient_email'] ?? '')), 'string', false);
        $this->put('notifications', 'smtp_host', trim((string) ($data['smtp_host'] ?? '')), 'string', false);
        $this->put('notifications', 'smtp_port', (string) max(1, min(65535, (int) ($data['smtp_port'] ?? 587))), 'integer', false);
        $this->put('notifications', 'smtp_username', trim((string) ($data['smtp_username'] ?? '')), 'string', false);
        $this->put('notifications', 'smtp_encryption', (string) ($data['smtp_encryption'] ?? 'tls'), 'string', false);
        $this->put('notifications', 'from_address', trim((string) ($data['from_address'] ?? '')), 'string', false);
        $this->put('notifications', 'from_name', trim((string) ($data['from_name'] ?? '')), 'string', false);

        if (filled($data['smtp_password'] ?? null)) {
            $this->put('notifications', 'smtp_password', Crypt::encryptString((string) $data['smtp_password']), 'secret', false);
        }

        AuditService::record('notifications.email.updated', after: [
            'enabled' => (bool) ($data['email_enabled'] ?? false),
            'recipient_email' => $data['recipient_email'] ?? null,
            'notify_new_order' => (bool) ($data['notify_new_order'] ?? false),
            'notify_payment_confirmed' => (bool) ($data['notify_payment_confirmed'] ?? false),
        ]);

        Notification::make()->success()->title('Configurações de e-mail salvas')->send();
        $this->mount();
    }

    public function sendTest(): void
    {
        abort_unless(static::canAccess(), 403);

        try {
            app(EmailNotificationService::class)->sendTest();
            Notification::make()->success()->title('E-mail de teste enviado')->body('Confira a caixa de entrada e também o spam.')->send();
        } catch (Throwable $e) {
            Notification::make()->danger()->title('Falha no teste de e-mail')->body($e->getMessage())->send();
        }
    }

    private function bool(string $key): bool
    {
        return filter_var(SystemSetting::value('notifications', $key, '0'), FILTER_VALIDATE_BOOL);
    }

    private function put(string $group, string $key, mixed $value, string $type, bool $public): void
    {
        SystemSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type, 'is_public' => $public],
        );
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->active === true
            && in_array($user?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }
}
