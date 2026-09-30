<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('raffles', 'collect_phone')) {
            DB::table('raffles')->update(['collect_phone' => true]);
        }
    }

    public function down(): void
    {
        // Não restauramos valores antigos porque não é possível saber quais
        // campanhas tinham a coleta de telefone desativada anteriormente.
    }
};
