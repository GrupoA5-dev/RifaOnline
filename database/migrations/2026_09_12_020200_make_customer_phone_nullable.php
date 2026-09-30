<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('customers')->whereNull('phone')->exists()) {
            throw new \RuntimeException('Não é possível tornar customers.phone obrigatório enquanto existirem clientes sem telefone.');
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};
