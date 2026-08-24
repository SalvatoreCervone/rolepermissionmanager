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

        if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'source_file')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('source_file')->nullable()->after('controller_action')->comment('Route definition source file');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('rolepermissionmanager.tables');
        $tableName = $tableNames['secured_resources'] ?? 'acl_secured_resources';

        if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'source_file')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('source_file');
            });
        }
    }
};
