<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Services\Payments\InterPixSettings;
use App\Services\Support\AuditService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InterPixSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.inter-pix-settings';

    protected static ?string $navigationLabel = 'Pix Banco Inter';
    protected static ?string $title = 'Pix Banco Inter';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public function mount(InterPixSettings $settings): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'enabled' => $settings->getBool('enabled'),
            'environment' => $settings->get('environment', 'sandbox'),
            'client_id' => $settings->get('client_id'),
            'client_secret' => null,
            'pix_key' => $settings->get('pix_key'),
            'account' => $settings->get('account'),
            'payment_expiration_minutes' => $settings->paymentExpirationMinutes(),
            'certificate_mode' => $settings->get('certificate_mode', 'pem'),
            'certificate_pfx' => null,
            'certificate_pfx_password' => null,
            'certificate_crt' => null,
            'private_key' => null,
            'webhook_url' => $settings->webhookUrl(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('enabled')
                    ->label('Ativar Pix automático Banco Inter')
                    ->helperText('Quando ativado, novas compras usam cobrança Pix dinâmica do Banco Inter e confirmação automática.'),

                Select::make('environment')
                    ->label('Ambiente')
                    ->options([
                        'sandbox' => 'Sandbox / Homologação',
                        'production' => 'Produção',
                    ])
                    ->required(),

                TextInput::make('client_id')
                    ->label('Client ID')
                    ->maxLength(255)
                    ->autocomplete(false),

                TextInput::make('client_secret')
                    ->label('Client Secret')
                    ->password()
                    ->revealable()
                    ->autocomplete(false)
                    ->helperText('Por segurança, o segredo já salvo nunca é exibido. Deixe vazio para mantê-lo.'),

                TextInput::make('pix_key')
                    ->label('Chave Pix da integração')
                    ->maxLength(255)
                    ->helperText('Informe a chave Pix vinculada à cobrança quando a integração estiver disponível.'),

                TextInput::make('account')
                    ->label('Conta corrente (opcional)')
                    ->maxLength(64)
                    ->helperText('Necessário apenas quando a integração estiver associada a mais de uma conta.'),

                TextInput::make('payment_expiration_minutes')
                    ->label('Tempo para pagamento (minutos)')
                    ->numeric()
                    ->rules(['integer', 'min:1', 'max:30'])
                    ->minValue(1)
                    ->maxValue(30)
                    ->default(5)
                    ->required()
                    ->helperText('Novas reservas feitas com o Pix Inter ativo usarão este prazo. Recomendado: 5 minutos.'),

                Select::make('certificate_mode')
                    ->label('Formato do certificado')
                    ->options([
                        'pem' => 'Certificado + chave privada (CRT/PEM + KEY/PEM)',
                        'pfx' => 'Arquivo PFX/P12',
                    ])
                    ->required(),

                FileUpload::make('certificate_pfx')
                    ->label('Novo certificado PFX/P12')
                    ->disk('local')
                    ->directory('secure/inter')
                    ->maxSize(2048)
                    ->previewable(false)
                    ->openable(false)
                    ->downloadable(false)
                    ->helperText('Aceita PFX/P12. O conteúdo será validado pelo OpenSSL ao salvar, sem depender do MIME informado pelo navegador.'),

                TextInput::make('certificate_pfx_password')
                    ->label('Senha do PFX/P12')
                    ->password()
                    ->revealable()
                    ->autocomplete(false)
                    ->helperText('Deixe vazio para manter a senha já cadastrada.'),

                FileUpload::make('certificate_crt')
                    ->label('Novo certificado CRT/PEM')
                    ->disk('local')
                    ->directory('secure/inter')
                    ->maxSize(2048)
                    ->previewable(false)
                    ->openable(false)
                    ->downloadable(false)
                    ->helperText('Aceita certificado CRT/PEM/CER. O conteúdo será validado pelo OpenSSL ao salvar.'),

                FileUpload::make('private_key')
                    ->label('Nova chave privada KEY/PEM')
                    ->disk('local')
                    ->directory('secure/inter')
                    ->maxSize(2048)
                    ->previewable(false)
                    ->openable(false)
                    ->downloadable(false)
                    ->helperText('Aceita KEY/PEM. O conteúdo será validado pelo OpenSSL ao salvar.'),

                TextInput::make('webhook_url')
                    ->label('URL do Webhook Inter')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Depois do WORK 7, use o comando inter:webhook-register para cadastrar esta URL automaticamente no Banco Inter.'),
            ])
            ->statePath('data');
    }

    public function save(InterPixSettings $settings): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $this->validateCertificateMaterial($settings, $data);

        $settings->put('enabled', ! empty($data['enabled']) ? '1' : '0', 'boolean');
        $settings->put('environment', $data['environment'] ?? 'sandbox');
        $settings->put('client_id', $data['client_id'] ?? null);
        $settings->put('pix_key', $data['pix_key'] ?? null);
        $settings->put('account', $data['account'] ?? null);
        $settings->put('payment_expiration_minutes', max(1, min(30, (int) ($data['payment_expiration_minutes'] ?? 5))), 'integer');
        $settings->put('certificate_mode', $data['certificate_mode'] ?? 'pem');

        $settings->putSecret('client_secret', $data['client_secret'] ?? null);
        $settings->putSecret('certificate_pfx_password', $data['certificate_pfx_password'] ?? null);

        $this->persistUploadedFile($settings, 'certificate_pfx_path', $data['certificate_pfx'] ?? null);
        $this->persistUploadedFile($settings, 'certificate_crt_path', $data['certificate_crt'] ?? null);
        $this->persistUploadedFile($settings, 'private_key_path', $data['private_key'] ?? null);

        AuditService::record('payments.inter_pix.settings.updated', after: [
            'enabled' => ! empty($data['enabled']),
            'environment' => $data['environment'] ?? 'sandbox',
            'client_id_configured' => filled($data['client_id'] ?? null),
            'pix_key_configured' => filled($data['pix_key'] ?? null),
            'certificate_mode' => $data['certificate_mode'] ?? 'pem',
            'payment_expiration_minutes' => max(1, min(30, (int) ($data['payment_expiration_minutes'] ?? 5))),
            'client_secret_configured' => $settings->hasSecret('client_secret'),
            'certificate_configured' => $settings->configurationStatus()['certificate'],
        ]);

        $status = $settings->configurationStatus();

        Notification::make()
            ->success()
            ->title('Configuração do Banco Inter salva')
            ->body($status['ready']
                ? 'As credenciais mínimas estão cadastradas. A integração ainda deve ser testada antes de ativar em produção.'
                : 'Configuração parcial salva. Você poderá completar Client ID, Client Secret e certificado posteriormente.')
            ->send();

        $this->mount($settings);
    }

    private function validateCertificateMaterial(InterPixSettings $settings, array $data): void
    {
        $mode = (string) ($data['certificate_mode'] ?? 'pem');

        if (! function_exists('openssl_x509_read')) {
            throw ValidationException::withMessages([
                'data.certificate_crt' => 'A extensão OpenSSL do PHP precisa estar habilitada para validar os certificados do Banco Inter.',
            ]);
        }

        if ($mode === 'pfx') {
            $pfxPath = $this->uploadedOrExistingPath(
                $settings,
                'certificate_pfx_path',
                $data['certificate_pfx'] ?? null,
            );

            if (! $pfxPath) {
                return;
            }

            $contents = @file_get_contents($pfxPath);
            $password = trim((string) ($data['certificate_pfx_password'] ?? ''));

            if ($password === '') {
                $password = (string) ($settings->getSecret('certificate_pfx_password') ?? '');
            }

            $certificates = [];
            $valid = is_string($contents)
                && function_exists('openssl_pkcs12_read')
                && @openssl_pkcs12_read($contents, $certificates, $password);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'data.certificate_pfx' => 'O arquivo PFX/P12 não pôde ser validado. Confira o arquivo e a senha do certificado.',
                ]);
            }

            return;
        }

        $certificatePath = $this->uploadedOrExistingPath(
            $settings,
            'certificate_crt_path',
            $data['certificate_crt'] ?? null,
        );
        $privateKeyPath = $this->uploadedOrExistingPath(
            $settings,
            'private_key_path',
            $data['private_key'] ?? null,
        );

        // Permite salvar outras configurações enquanto certificado/chave ainda não foram enviados.
        if (! $certificatePath && ! $privateKeyPath) {
            return;
        }

        if (! $certificatePath) {
            throw ValidationException::withMessages([
                'data.certificate_crt' => 'Envie o certificado CRT/PEM correspondente à chave privada.',
            ]);
        }

        if (! $privateKeyPath) {
            throw ValidationException::withMessages([
                'data.private_key' => 'Envie a chave privada KEY/PEM correspondente ao certificado.',
            ]);
        }

        $certificateContents = @file_get_contents($certificatePath);
        $privateKeyContents = @file_get_contents($privateKeyPath);

        $certificate = is_string($certificateContents) ? @openssl_x509_read($certificateContents) : false;
        if ($certificate === false) {
            throw ValidationException::withMessages([
                'data.certificate_crt' => 'O arquivo enviado não contém um certificado X.509 válido (CRT/PEM).',
            ]);
        }

        $privateKey = is_string($privateKeyContents) ? @openssl_pkey_get_private($privateKeyContents) : false;
        if ($privateKey === false) {
            throw ValidationException::withMessages([
                'data.private_key' => 'O arquivo enviado não contém uma chave privada PEM/KEY válida.',
            ]);
        }

        if (! @openssl_x509_check_private_key($certificate, $privateKey)) {
            throw ValidationException::withMessages([
                'data.private_key' => 'A chave privada não corresponde ao certificado enviado. Use o par CRT + KEY gerado na mesma integração do Banco Inter.',
            ]);
        }
    }

    private function uploadedOrExistingPath(
        InterPixSettings $settings,
        string $settingKey,
        mixed $uploadedState,
    ): ?string {
        $relativePath = $this->normalizeUploadPath($uploadedState);

        if (is_string($relativePath) && $relativePath !== '' && Storage::disk('local')->exists($relativePath)) {
            return Storage::disk('local')->path($relativePath);
        }

        return $settings->absoluteFilePath($settingKey);
    }

    private function persistUploadedFile(InterPixSettings $settings, string $settingKey, mixed $state): void
    {
        $path = $this->normalizeUploadPath($state);

        if (! $path) {
            return;
        }

        $oldPath = $settings->get($settingKey);

        if (is_string($oldPath) && $oldPath !== $path && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        $settings->put($settingKey, $path, 'private_file');
    }

    private function normalizeUploadPath(mixed $state): ?string
    {
        if (is_string($state) && $state !== '') {
            return $state;
        }

        if (is_array($state)) {
            $first = reset($state);
            return is_string($first) && $first !== '' ? $first : null;
        }

        return null;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->active === true
            && $user?->role === UserRole::SuperAdmin;
    }
}
