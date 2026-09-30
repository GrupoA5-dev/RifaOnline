<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->text('access_pin')->nullable()->after('public_token');
        });

        DB::table('customers')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($customers): void {
                foreach ($customers as $customer) {
                    $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->whereNull('access_pin')
                        ->update(['access_pin' => Crypt::encryptString($pin)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('access_pin');
        });
    }
};
