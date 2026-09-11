<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('rolepermissionmanager.tables');

        $securedResourcesTable = $tableNames['secured_resources'] ?? 'acl_secured_resources';
        $routeParameterRulesTable = $tableNames['route_parameter_rules'] ?? 'acl_route_parameter_rules';
        $pivotTable = $tableNames['parameter_rule_has_permissions'] ?? 'acl_parameter_rule_has_permissions';
        $permissionsTable = $tableNames['permissions'] ?? 'acl_permissions';

        // 1. Add unmatched_parameter_behavior column to secured_resources table
        if (Schema::hasTable($securedResourcesTable) && !Schema::hasColumn($securedResourcesTable, 'unmatched_parameter_behavior')) {
            Schema::table($securedResourcesTable, function (Blueprint $table) {
                $table->string('unmatched_parameter_behavior', 20)->default('allow')->after('operator')
                    ->comment('Behavior for unmapped parameter values: allow, deny_403, deny_404');
            });
        }

        // 2. Create route_parameter_rules table
        if (!Schema::hasTable($routeParameterRulesTable)) {
            Schema::create($routeParameterRulesTable, function (Blueprint $table) use ($securedResourcesTable) {
                $table->id();
                $table->unsignedBigInteger('secured_resource_id');
                $table->string('parameter_name', 100)->comment('E.g. page, destinazione');
                $table->string('parameter_value')->comment('E.g. elenco, statistiche');
                $table->boolean('is_public')->default(false);
                $table->boolean('is_super_admin_only')->default(false);
                $table->enum('operator', ['OR', 'AND'])->default('OR');
                $table->timestamps();

                $table->foreign('secured_resource_id')
                    ->references('id')
                    ->on($securedResourcesTable)
                    ->cascadeOnDelete();

                $table->unique(['secured_resource_id', 'parameter_name', 'parameter_value'], 'acl_param_rule_unique');
            });
        }

        // 3. Create parameter_rule_has_permissions pivot table
        if (!Schema::hasTable($pivotTable)) {
            Schema::create($pivotTable, function (Blueprint $table) use ($routeParameterRulesTable, $permissionsTable) {
                $table->unsignedBigInteger('parameter_rule_id');
                $table->unsignedBigInteger('permission_id');

                $table->foreign('parameter_rule_id', 'acl_prhp_rule_fk')
                    ->references('id')
                    ->on($routeParameterRulesTable)
                    ->cascadeOnDelete();

                $table->foreign('permission_id', 'acl_prhp_perm_fk')
                    ->references('id')
                    ->on($permissionsTable)
                    ->cascadeOnDelete();

                $table->primary(['parameter_rule_id', 'permission_id'], 'acl_prhp_pk');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('rolepermissionmanager.tables');

        $securedResourcesTable = $tableNames['secured_resources'] ?? 'acl_secured_resources';
        $routeParameterRulesTable = $tableNames['route_parameter_rules'] ?? 'acl_route_parameter_rules';
        $pivotTable = $tableNames['parameter_rule_has_permissions'] ?? 'acl_parameter_rule_has_permissions';

        Schema::dropIfExists($pivotTable);
        Schema::dropIfExists($routeParameterRulesTable);

        if (Schema::hasTable($securedResourcesTable) && Schema::hasColumn($securedResourcesTable, 'unmatched_parameter_behavior')) {
            Schema::table($securedResourcesTable, function (Blueprint $table) {
                $table->dropColumn('unmatched_parameter_behavior');
            });
        }
    }
};
