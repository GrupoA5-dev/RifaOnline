<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $logo = SystemSetting::value('branding', 'logo');
        $favicon = SystemSetting::value('branding', 'favicon');
        $footerYear = trim((string) SystemSetting::value('footer', 'year', ''));

        return [
            ...parent::share($request),
            'app' => [
                'name' => SystemSetting::value('general', 'system_name', config('app.name')),
                'version' => config('app.version'),
                'branding' => [
                    'logo' => $logo,
                    'logo_url' => $this->storageUrl($logo),
                    'logo_size' => max(28, min(120, (int) SystemSetting::value('branding', 'logo_size', 44))),
                    'favicon_url' => $this->storageUrl($favicon),
                    'tagline' => SystemSetting::value('branding', 'tagline', 'Campanhas digitais'),
                    'primary_color' => SystemSetting::value('branding', 'primary_color', '#22c55e'),
                    'button_text_color' => SystemSetting::value('branding', 'button_text_color', '#07110b'),
                ],
                'seo' => [
                    'site_title' => SystemSetting::value('seo', 'site_title', SystemSetting::value('general', 'system_name', config('app.name'))),
                    'description' => SystemSetting::value('seo', 'description', ''),
                    'keywords' => SystemSetting::value('seo', 'keywords', ''),
                    'charset' => 'UTF-8',
                ],
                'footer' => [
                    'owner' => SystemSetting::value('footer', 'owner', SystemSetting::value('general', 'system_name', config('app.name'))),
                    'year' => $footerYear !== '' ? $footerYear : now()->format('Y'),
                    'rights' => SystemSetting::value('footer', 'rights', 'Todos os direitos reservados.'),
                    'developer_name' => SystemSetting::value('footer', 'developer_name', ''),
                    'developer_url' => SystemSetting::value('footer', 'developer_url', ''),
                ],
                'contact' => [
                    'whatsapp' => SystemSetting::value('contact', 'whatsapp'),
                    'email' => SystemSetting::value('contact', 'email'),
                    'instagram' => SystemSetting::value('social', 'instagram'),
                ],
                'urls' => [
                    'home' => route('home'),
                    'admin' => url('/admin'),
                    'my_numbers' => route('my-numbers.lookup'),
                ],
            ],
        ];
    }

    private function storageUrl(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? Storage::disk('public')->url($value) : null;
    }
}
