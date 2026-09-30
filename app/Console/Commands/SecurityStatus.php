<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Payments\InterPixSettings;
use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityStatus extends Command
{
    protected $signature = 'a5:security-status';
    protected $description = 'Verifica os controles de segurança e prontidão final de produção.';

    public function handle(InterPixSettings $interSettings): int
    {
        $checks = [];

        $checks['environment_production'] = app()->environment('production');
        $checks['debug_off'] = config('app.debug') === false;
        $checks['force_https'] = (bool) config('app.force_https');
        $checks['secure_session_cookie'] = (bool) config('session.secure');
        $checks['webhook_route'] = Route::has('webhooks.inter.pix');
        $checks['my_numbers_route'] = Route::has('my-numbers.lookup.submit');
        $checks['customer_access_pin_column'] = Schema::hasColumn('customers', 'access_pin');
        $checks['mfa_secret_column'] = Schema::hasColumn('users', 'app_authentication_secret');
        $checks['mfa_recovery_column'] = Schema::hasColumn('users', 'app_authentication_recovery_codes');

        $adminTwoFactorEnabled = true;
        if (Schema::hasTable('system_settings')) {
            $adminTwoFactorEnabled = filter_var(
                SystemSetting::value('security', 'admin_2fa_enabled', '1'),
                FILTER_VALIDATE_BOOL,
            );
        }

        $filamentVersion = null;
        try {
            $filamentVersion = InstalledVersions::getPrettyVersion('filament/filament')
                ?? InstalledVersions::getVersion('filament/filament');
        } catch (Throwable) {
            // Mantém nulo.
        }

        $normalizedFilamentVersion = is_string($filamentVersion)
            ? ltrim(preg_replace('/[^0-9.].*$/', '', ltrim($filamentVersion, 'v')) ?? '', 'v')
            : '';

        $checks['filament_mfa_security_patch'] = $normalizedFilamentVersion !== ''
            && version_compare($normalizedFilamentVersion, '5.7.0', '>=');

        $activeAdmins = 0;
        $adminsWithMfa = 0;
        if (Schema::hasTable('users') && $checks['mfa_secret_column']) {
            $activeAdmins = User::query()->where('active', true)->count();
            $adminsWithMfa = User::query()
                ->where('active', true)
                ->whereNotNull('app_authentication_secret')
                ->where('app_authentication_secret', '<>', '')
                ->count();
        }
        $checks['admin_2fa_consistency'] = ! $adminTwoFactorEnabled
            || ($activeAdmins > 0 && $activeAdmins === $adminsWithMfa);

        $customersWithoutPin = 0;
        if (Schema::hasTable('customers') && $checks['customer_access_pin_column']) {
            $customersWithoutPin = Customer::query()->whereNull('access_pin')->count();
        }
        $checks['all_customers_have_access_pin'] = $customersWithoutPin === 0;

        $heartbeat = Cache::get('a5:ops:last_scheduler_heartbeat');
        $schedulerRecent = false;
        if (is_string($heartbeat) && $heartbeat !== '') {
            try {
                $schedulerRecent = now()->diffInMinutes(\Carbon\Carbon::parse($heartbeat), true) <= 5;
            } catch (Throwable) {
                $schedulerRecent = false;
            }
        }
        $checks['scheduler_recent'] = $schedulerRecent;

        try {
            $checks['inter_pix_ready'] = $interSettings->configurationStatus()['ready'] ?? false;
        } catch (Throwable) {
            $checks['inter_pix_ready'] = false;
        }

        $this->line('A5 Rifas — Security & Production Finalization');
        $this->line('APP_VERSION: '.(string) config('app.version'));
        $this->line('Filament: '.($filamentVersion ?: 'não identificado'));
        $this->line('2FA administrativo: '.($adminTwoFactorEnabled ? 'ATIVO' : 'DESATIVADO POR CONFIGURAÇÃO'));
        $this->newLine();

        $fails = 0;
        foreach ($checks as $name => $ok) {
            $this->line(str_pad($name, 38).($ok ? 'OK' : 'PENDENTE'));
            if (! $ok) {
                $fails++;
            }
        }

        $this->newLine();
        $this->line("Admins ativos com 2FA configurado: {$adminsWithMfa}/{$activeAdmins}");
        $this->line("Clientes sem código de acesso: {$customersWithoutPin}");

        if ($fails > 0) {
            $this->newLine();
            $this->warn("Há {$fails} item(ns) pendente(s). Revise os itens acima e rode o comando novamente.");
            return self::FAILURE;
        }

        $this->newLine();
        if (! $adminTwoFactorEnabled) {
            $this->warn('WORK 11 operacional, porém o 2FA administrativo está desativado por configuração. Reative-o quando possível.');
        } else {
            $this->info('WORK 11 aprovado: controles essenciais de produção estão ativos.');
        }

        return self::SUCCESS;
    }
}
