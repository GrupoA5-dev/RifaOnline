<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform permissions are opt-in. Organizer membership is stored
     * separately in organizer_users.role (master/manager).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 32)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Never silently turn tenant-only users into platform admins.
        if (DB::table('users')->whereNull('role')->exists()) {
            throw new RuntimeException(
                'Cannot restore admin default while tenant-only users exist.'
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 32)->nullable(false)->default('admin')->change();
        });
    }
};
