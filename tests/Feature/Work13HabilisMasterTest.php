<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\Plan;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Work13HabilisMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => UserRole::SuperAdmin]);

        Organizer::query()->create([
            'name' => 'Rifa Habilis',
            'slug' => 'rifa-habilis',
            'status' => 'active',
        ]);

        Plan::query()->create([
            'name' => 'Trial 15 dias',
            'slug' => 'trial',
            'price_cents' => 0,
            'features' => ['trial_days' => 15],
            'active' => true,
        ]);
    }

    public function test_creates_exactly_one_master_with_no_platform_permissions(): void
    {
        $this->artisan('a5:work13-create-habilis-master')
            ->expectsQuestion('Nome do Master', 'Master Habilis')
            ->expectsQuestion('E-mail do Master (diferente do Super Admin)', 'habilis@example.test')
            ->expectsQuestion('Senha forte do Master (entrada oculta)', 'UmaSenhaForte#2026')
            ->expectsQuestion('Confirme a senha do Master (entrada oculta)', 'UmaSenhaForte#2026')
            ->expectsOutput('MASTER=CRIADO')
            ->assertSuccessful();

        $user = User::query()->where('email', 'habilis@example.test')->firstOrFail();

        $this->assertNull($user->role);
        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
        $this->assertTrue($user->active);
        $this->assertDatabaseHas('organizer_users', [
            'organizer_id' => Organizer::query()->where('slug', 'rifa-habilis')->value('id'),
            'user_id' => $user->id,
            'role' => 'master',
        ]);
        $this->assertSame(1, DB::table('organizer_users')->where('role', 'master')->count());
        $this->assertSame(1, User::query()->where('role', UserRole::SuperAdmin->value)->count());
        $this->assertSame(0, DB::table('subscriptions')->count());
    }

    public function test_refuses_to_create_another_master(): void
    {
        $organizer = Organizer::query()->where('slug', 'rifa-habilis')->firstOrFail();
        $master = User::factory()->create(['role' => null]);
        $organizer->users()->attach($master->id, ['role' => 'master']);

        $this->artisan('a5:work13-create-habilis-master')
            ->assertFailed();

        $this->assertSame(1, DB::table('organizer_users')->where('role', 'master')->count());
        $this->assertSame(2, User::query()->count());
    }

    public function test_rejects_using_the_platform_admin_email(): void
    {
        $adminEmail = User::query()->where('role', UserRole::SuperAdmin->value)->value('email');

        $this->artisan('a5:work13-create-habilis-master')
            ->expectsQuestion('Nome do Master', 'Master Habilis')
            ->expectsQuestion('E-mail do Master (diferente do Super Admin)', $adminEmail)
            ->expectsQuestion('Senha forte do Master (entrada oculta)', 'UmaSenhaForte#2026')
            ->expectsQuestion('Confirme a senha do Master (entrada oculta)', 'UmaSenhaForte#2026')
            ->assertFailed();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, DB::table('organizer_users')->count());
    }
}
