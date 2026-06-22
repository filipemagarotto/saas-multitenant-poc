<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cada usuario passa a pertencer a UM tenant. Combinado com o trait
     * BelongsToTenant no model User, o login fica isolado por tenant: a busca
     * por credenciais ja vem filtrada pelo tenant atual.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // O e-mail passa a ser unico POR tenant (o mesmo e-mail pode existir
            // em tenants diferentes). Remove o unique global da migration base.
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
            $table->unique('email');
        });
    }
};
