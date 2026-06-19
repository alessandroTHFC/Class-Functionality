<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('role_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('permission_id');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
