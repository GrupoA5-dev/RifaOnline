<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Work5Status extends Command
{
    protected $signature = 'a5:work5-status';
    protected $description = 'Valida branding, metas de campanha e dashboard comercial do WORK 5.';

    public function handle(): int
    {
        $checks = [
            'raffle_goals' => Schema::hasColumns('raffles', ['revenue_goal_cents', 'sales_goal_numbers']),
            'branding_page' => class_exists(\App\Filament\Pages\BrandingSettingsPage::class),
            'dashboard_page' => class_exists(\App\Filament\Pages\Dashboard::class),
            'sales_overview' => class_exists(\App\Filament\Widgets\SalesOverview::class),
            'revenue_chart' => class_exists(\App\Filament\Widgets\RevenueChart::class),
            'campaign_performance' => class_exists(\App\Filament\Widgets\CampaignPerformance::class),
            'recent_sales' => class_exists(\App\Filament\Widgets\RecentSales::class),
        ];

        foreach ($checks as $name => $ok) {
            $this->line(str_pad($name, 30).($ok ? 'OK' : 'ERRO'));
        }

        if (in_array(false, $checks, true)) {
            $this->error('WORK 5 incompleto.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('WORK 5 instalado corretamente.');

        return self::SUCCESS;
    }
}
