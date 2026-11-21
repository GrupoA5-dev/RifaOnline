<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformRoleDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_organizer_user_has_no_implicit_platform_role(): void
    {
        $user = User::factory()->create(['role' => null]);

        $this->assertNull($user->fresh()->role);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => null,
        ]);
    }

    public function test_database_does_not_default_to_admin_for_new_users(): void
    {
        $id = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'Tenant-only User',
            'email' => 'tenant-only@example.test',
            'password' => bcrypt('not-a-production-password'),
            'active' => true,
        ]);

        $this->assertNull(User::query()->findOrFail($id)->role);
    }

    public function test_a_platform_admin_role_remains_explicit(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->assertSame(UserRole::SuperAdmin, $user->fresh()->role);
    }
}
