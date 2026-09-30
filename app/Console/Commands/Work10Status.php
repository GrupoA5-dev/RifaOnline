<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Work10Status extends Command
{
    protected $signature = 'a5:work10-status';
    protected $description = 'Verifica os principais componentes do WORK 10.';

    public function handle(): int
    {
        $checks = [
            'raffle_contact_seo' => Schema::hasColumns('raffles', ['instagram_url', 'whatsapp_url', 'seo_title', 'seo_description', 'seo_keywords']),
            'raffle_events' => Schema::hasTable('raffle_events'),
            'customer_archive' => Schema::hasColumn('customers', 'archived_at'),
            'seo_settings_page' => class_exists(\App\Filament\Pages\SeoSettingsPage::class),
            'email_settings_page' => class_exists(\App\Filament\Pages\EmailNotificationSettingsPage::class),
            'analytics_controller' => class_exists(\App\Http\Controllers\Public\RaffleEventController::class),
            'analytics_widget' => class_exists(\App\Filament\Widgets\CampaignAnalyticsOverview::class),
        ];

        foreach ($checks as $label => $ok) {
            $this->line(str_pad($label, 28).($ok ? '<fg=green>OK</>' : '<fg=red>FAIL</>'));
        }

        if (in_array(false, $checks, true)) {
            $this->error('WORK 10 incompleto.');
            return self::FAILURE;
        }

        $this->info('WORK 10 instalado corretamente.');
        return self::SUCCESS;
    }
}
