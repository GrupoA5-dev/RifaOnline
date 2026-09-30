<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['general', 'system_name', config('app.name'), 'string', true],
            ['branding', 'logo', null, 'image', true],
            ['branding', 'tagline', 'Campanhas digitais', 'string', true],
            ['branding', 'logo_size', 44, 'integer', true],
            ['branding', 'favicon', null, 'image', true],
            ['branding', 'primary_color', '#22c55e', 'color', true],
            ['branding', 'button_text_color', '#07110b', 'color', true],
            ['contact', 'whatsapp', null, 'string', true],
            ['contact', 'email', null, 'string', true],
            ['social', 'instagram', null, 'string', true],
            ['footer', 'owner', config('app.name'), 'string', true],
            ['footer', 'year', '', 'integer', true],
            ['footer', 'rights', 'Todos os direitos reservados.', 'string', true],
            ['footer', 'developer_name', '', 'string', true],
            ['footer', 'developer_url', '', 'string', true],
            ['seo', 'site_title', config('app.name'), 'string', true],
            ['seo', 'description', '', 'string', true],
            ['seo', 'keywords', '', 'string', true],
            ['seo', 'default_share_image', null, 'image', true],
            ['notifications', 'email_enabled', '0', 'boolean', false],
            ['notifications', 'notify_new_order', '1', 'boolean', false],
            ['notifications', 'notify_payment_confirmed', '1', 'boolean', false],
            ['notifications', 'recipient_email', null, 'string', false],
            ['notifications', 'smtp_host', null, 'string', false],
            ['notifications', 'smtp_port', '587', 'integer', false],
            ['notifications', 'smtp_username', null, 'string', false],
            ['notifications', 'smtp_password', null, 'secret', false],
            ['notifications', 'smtp_encryption', 'tls', 'string', false],
            ['notifications', 'from_address', null, 'string', false],
            ['notifications', 'from_name', config('app.name'), 'string', false],
            ['payments.inter_pix', 'payment_expiration_minutes', '5', 'integer', false],
            ['payments.inter_pix', 'expiry_grace_seconds', '60', 'integer', false],
        ];

        foreach ($settings as [$group, $key, $value, $type, $public]) {
            SystemSetting::query()->firstOrCreate(
                compact('group', 'key'),
                ['value' => $value, 'type' => $type, 'is_public' => $public],
            );
        }
    }
}
