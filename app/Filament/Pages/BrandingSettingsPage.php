<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Services\Support\AuditService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BrandingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.branding-settings';

    protected static ?string $navigationLabel = 'Identidade visual';
    protected static ?string $title = 'Identidade visual';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'system_name' => SystemSetting::value('general', 'system_name', config('app.name')),
            'tagline' => SystemSetting::value('branding', 'tagline', 'Campanhas digitais'),
            'logo' => SystemSetting::value('branding', 'logo'),
            'logo_size' => (int) SystemSetting::value('branding', 'logo_size', 44),
            'favicon' => SystemSetting::value('branding', 'favicon'),
            'primary_color' => SystemSetting::value('branding', 'primary_color', '#22c55e'),
            'button_text_color' => SystemSetting::value('branding', 'button_text_color', '#07110b'),
            'footer_owner' => SystemSetting::value('footer', 'owner', SystemSetting::value('general', 'system_name', config('app.name'))),
            'footer_year' => SystemSetting::value('footer', 'year', ''),
            'footer_rights' => SystemSetting::value('footer', 'rights', 'Todos os direitos reservados.'),
            'footer_developer_name' => SystemSetting::value('footer', 'developer_name', ''),
            'footer_developer_url' => SystemSetting::value('footer', 'developer_url', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('system_name')
                    ->label('Nome do site')
                    ->required()
                    ->maxLength(80),

                TextInput::make('tagline')
                    ->label('Subtítulo / slogan')
                    ->maxLength(120)
                    ->helperText('Exibido abaixo do nome no cabeçalho público.'),

                FileUpload::make('logo')
                    ->label('Logo do site')
                    ->image()
                    ->disk('public')
                    ->directory('branding')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(4096)
                    ->imagePreviewHeight('100')
                    ->helperText('PNG, JPG ou WebP. Até 4 MB.'),

                TextInput::make('logo_size')
                    ->label('Tamanho da logo no site e na Gestão')
                    ->type('range')
                    ->rules(['required', 'integer', 'min:28', 'max:120'])
                    ->minValue(28)
                    ->maxValue(120)
                    ->step(1)
                    ->helperText('A mesma altura é aplicada ao site público, tela de login e painel Gestão. Faixa: 28px a 120px.'),

                FileUpload::make('favicon')
                    ->label('Favicon')
                    ->image()
                    ->disk('public')
                    ->directory('branding/favicon')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(1024)
                    ->imagePreviewHeight('64')
                    ->helperText('Use uma imagem quadrada, preferencialmente PNG ou WebP.'),

                ColorPicker::make('primary_color')
                    ->label('Cor principal / botões')
                    ->required()
                    ->hexColor()
                    ->default('#22c55e'),

                ColorPicker::make('button_text_color')
                    ->label('Cor do texto dos botões')
                    ->required()
                    ->hexColor()
                    ->default('#07110b'),

                TextInput::make('footer_owner')
                    ->label('Nome exibido no rodapé')
                    ->maxLength(120),
                TextInput::make('footer_year')
                    ->label('Ano do rodapé')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2200)
                    ->helperText('Deixe vazio para usar automaticamente o ano atual.'),
                TextInput::make('footer_rights')
                    ->label('Texto de direitos')
                    ->maxLength(160)
                    ->placeholder('Todos os direitos reservados.'),
                TextInput::make('footer_developer_name')
                    ->label('Desenvolvido por')
                    ->maxLength(120),
                TextInput::make('footer_developer_url')
                    ->label('Link do desenvolvedor')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://...'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $oldLogo = SystemSetting::value('branding', 'logo');
        $newLogo = $this->normalizeUploadPath($data['logo'] ?? null);
        $oldFavicon = SystemSetting::value('branding', 'favicon');
        $newFavicon = $this->normalizeUploadPath($data['favicon'] ?? null);

        $this->deleteReplacedUpload($oldLogo, $newLogo);
        $this->deleteReplacedUpload($oldFavicon, $newFavicon);

        $this->put('general', 'system_name', trim((string) ($data['system_name'] ?? config('app.name'))), 'string', true);
        $this->put('branding', 'tagline', trim((string) ($data['tagline'] ?? '')), 'string', true);
        $this->put('branding', 'logo', $newLogo, 'image', true);
        $logoSize = max(28, min(120, (int) ($data['logo_size'] ?? 44)));
        $this->put('branding', 'logo_size', $logoSize, 'integer', true);
        $this->put('branding', 'favicon', $newFavicon, 'image', true);
        $this->put('branding', 'primary_color', strtolower((string) ($data['primary_color'] ?? '#22c55e')), 'color', true);
        $this->put('branding', 'button_text_color', strtolower((string) ($data['button_text_color'] ?? '#07110b')), 'color', true);
        $this->put('footer', 'owner', trim((string) ($data['footer_owner'] ?? '')), 'string', true);
        $this->put('footer', 'year', blank($data['footer_year'] ?? null) ? '' : (string) (int) $data['footer_year'], 'integer', true);
        $this->put('footer', 'rights', trim((string) ($data['footer_rights'] ?? '')), 'string', true);
        $this->put('footer', 'developer_name', trim((string) ($data['footer_developer_name'] ?? '')), 'string', true);
        $this->put('footer', 'developer_url', trim((string) ($data['footer_developer_url'] ?? '')), 'string', true);

        AuditService::record('branding.updated', after: [
            'system_name' => $data['system_name'] ?? null,
            'tagline' => $data['tagline'] ?? null,
            'logo_configured' => filled($newLogo),
            'favicon_configured' => filled($newFavicon),
            'logo_size' => $logoSize,
            'primary_color' => $data['primary_color'] ?? null,
        ]);

        Notification::make()
            ->success()
            ->title('Identidade visual atualizada')
            ->body('Logo, tamanho, favicon, cores e rodapé foram salvos.')
            ->send();

        $this->mount();
    }

    private function deleteReplacedUpload(mixed $old, ?string $new): void
    {
        if (is_string($old) && $old !== '' && $old !== $new && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
    }

    private function put(string $group, string $key, mixed $value, string $type, bool $public): void
    {
        SystemSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type, 'is_public' => $public],
        );
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
            && in_array($user?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }
}
