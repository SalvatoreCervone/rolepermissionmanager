<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('rolepermissionmanager.tables');
        $tableName = $tableNames['secured_resources'] ?? 'acl_secured_resources';

        if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'is_unconfigured')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('is_unconfigured')->default(false)->after('is_deprecated')->comment('Flagged true if auto-discovered and pending manual configuration');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('rolepermissionmanager.tables');
        $tableName = $tableNames['secured_resources'] ?? 'acl_secured_resources';

        if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'is_unconfigured')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('is_unconfigured');
            });
        }
    }
};
