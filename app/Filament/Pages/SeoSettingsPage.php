<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Services\Support\AuditService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SeoSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.seo-settings';

    protected static ?string $navigationLabel = 'SEO e compartilhamento';
    protected static ?string $title = 'SEO e compartilhamento';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'site_title' => SystemSetting::value('seo', 'site_title', SystemSetting::value('general', 'system_name', config('app.name'))),
            'description' => SystemSetting::value('seo', 'description', ''),
            'keywords' => SystemSetting::value('seo', 'keywords', ''),
            'default_share_image' => SystemSetting::value('seo', 'default_share_image'),
            'charset' => 'UTF-8',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('site_title')
                ->label('Título padrão do site')
                ->required()
                ->maxLength(120),
            Textarea::make('description')
                ->label('Meta description padrão')
                ->rows(3)
                ->maxLength(500),
            Textarea::make('keywords')
                ->label('Meta keywords')
                ->rows(3)
                ->helperText('Separe as palavras-chave por vírgulas.'),
            FileUpload::make('default_share_image')
                ->label('Imagem padrão de compartilhamento')
                ->image()
                ->disk('public')
                ->directory('branding/share')
                ->visibility('public')
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->maxSize(4096)
                ->helperText('Usada quando uma campanha não possui imagem própria. Recomendado: 1200×630 px.'),
            TextInput::make('charset')
                ->label('Charset')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Mantido em UTF-8 para preservar acentos, emojis e compatibilidade.'),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $oldImage = SystemSetting::value('seo', 'default_share_image');
        $newImage = $this->normalizeUploadPath($data['default_share_image'] ?? null);

        if (is_string($oldImage) && $oldImage !== '' && $oldImage !== $newImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        $this->put('seo', 'site_title', trim((string) ($data['site_title'] ?? config('app.name'))), 'string', true);
        $this->put('seo', 'description', trim((string) ($data['description'] ?? '')), 'string', true);
        $this->put('seo', 'keywords', trim((string) ($data['keywords'] ?? '')), 'string', true);
        $this->put('seo', 'default_share_image', $newImage, 'image', true);

        AuditService::record('seo.updated', after: [
            'site_title' => $data['site_title'] ?? null,
            'share_image_configured' => filled($newImage),
        ]);

        Notification::make()->success()->title('SEO e compartilhamento atualizados')->send();
        $this->mount();
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
