<?php

namespace App\Services\Seo;

use App\Models\Raffle;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SeoMetadataService
{
    public function forRequest(Request $request): array
    {
        $siteName = trim((string) SystemSetting::value('seo', 'site_title', SystemSetting::value('general', 'system_name', config('app.name'))));
        $description = trim((string) SystemSetting::value('seo', 'description', ''));
        $keywords = trim((string) SystemSetting::value('seo', 'keywords', ''));
        $defaultImage = $this->publicStorageUrl(SystemSetting::value('seo', 'default_share_image'));
        $favicon = $this->publicStorageUrl(SystemSetting::value('branding', 'favicon'));

        $metadata = [
            'title' => $siteName !== '' ? $siteName : (string) config('app.name'),
            'page_title' => $siteName !== '' ? $siteName : (string) config('app.name'),
            'description' => $description,
            'keywords' => $keywords,
            'charset' => 'UTF-8',
            'url' => $request->url(),
            'image' => $defaultImage,
            'favicon' => $favicon,
            'site_name' => $siteName !== '' ? $siteName : (string) config('app.name'),
            'type' => 'website',
        ];

        if ($request->route()?->getName() !== 'raffles.show') {
            return $metadata;
        }

        $slug = (string) $request->route('slug');
        $raffle = Raffle::query()->where('slug', $slug)->first();

        if (! $raffle) {
            return $metadata;
        }

        $pageTitle = trim((string) ($raffle->seo_title ?: $raffle->title));
        $raffleDescription = trim((string) ($raffle->seo_description ?: $raffle->description));
        $raffleDescription = Str::limit(preg_replace('/\s+/u', ' ', strip_tags($raffleDescription)) ?: '', 190, '');
        $raffleKeywords = trim((string) ($raffle->seo_keywords ?: $keywords));
        $cover = $this->mediaUrl($raffle->cover_image) ?: $defaultImage;

        return [
            ...$metadata,
            'title' => $pageTitle !== '' && $metadata['site_name'] !== '' && $pageTitle !== $metadata['site_name']
                ? $pageTitle.' — '.$metadata['site_name']
                : ($pageTitle !== '' ? $pageTitle : $metadata['title']),
            'page_title' => $pageTitle !== '' ? $pageTitle : $metadata['page_title'],
            'description' => $raffleDescription !== '' ? $raffleDescription : $description,
            'keywords' => $raffleKeywords,
            'image' => $cover,
            'type' => 'website',
        ];
    }

    private function publicStorageUrl(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $url = Storage::disk('public')->url($value);

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://') ? $url : url($url);
    }

    private function mediaUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}
