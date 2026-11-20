<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_enter_only_own_organization_and_not_platform_admin(): void
    {
        $habilis = Organizer::query()->create([
            'name' => 'Rifa Habilis',
            'slug' => 'rifa-habilis',
            'status' => 'active',
        ]);
        $other = Organizer::query()->create([
            'name' => 'Outro Cliente',
            'slug' => 'outro-cliente',
            'status' => 'active',
        ]);

        $master = User::factory()->create(['role' => null]);
        $master->organizers()->attach($habilis->id, ['role' => 'master']);

        $this->assertFalse($master->canAccessPanel(Panel::make()->id('admin')));
        $this->assertTrue($master->canAccessPanel(Panel::make()->id('organizer')));
        $this->assertFalse($master->canAccessPanel(Panel::make()->id('unknown')));
        $this->assertTrue($master->canAccessTenant($habilis));
        $this->assertFalse($master->canAccessTenant($other));
        $this->assertEquals(
            [$habilis->id],
            $master->getTenants(Panel::make()->id('organizer'))->pluck('id')->all(),
        );
        $this->assertCount(0, $master->getTenants(Panel::make()->id('admin')));
    }

    public function test_platform_super_admin_has_no_automatic_organizer_access(): void
    {
        $organizer = Organizer::query()->create([
            'name' => 'Rifa Habilis',
            'slug' => 'rifa-habilis',
            'status' => 'active',
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->assertTrue($admin->canAccessPanel(Panel::make()->id('admin')));
        $this->assertFalse($admin->canAccessPanel(Panel::make()->id('organizer')));
        $this->assertFalse($admin->canAccessTenant($organizer));
    }

    public function test_blocked_organizer_and_inactive_user_are_denied(): void
    {
        $organizer = Organizer::query()->create([
            'name' => 'Rifa Habilis',
            'slug' => 'rifa-habilis',
            'status' => 'blocked',
        ]);

        $master = User::factory()->create(['role' => null]);
        $master->organizers()->attach($organizer->id, ['role' => 'master']);

        $this->assertFalse($master->canAccessPanel(Panel::make()->id('organizer')));
        $this->assertFalse($master->canAccessTenant($organizer));
        $this->assertCount(0, $master->getTenants(Panel::make()->id('organizer')));

        $organizer->update(['status' => 'active']);
        $master->update(['active' => false]);

        $this->assertFalse($master->canAccessPanel(Panel::make()->id('organizer')));
        $this->assertFalse($master->canAccessTenant($organizer));
        $this->assertCount(0, $master->getTenants(Panel::make()->id('organizer')));
    }
}
