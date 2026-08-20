<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('rolepermissionmanager.tables');

        // Pivot: Model (User) ↔ Roles (polymorphic)
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_pk');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
        });

        // Pivot: Model (User) ↔ Permissions (polymorphic, for direct permission assignment)
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_pk');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
        });

        // Pivot: Role ↔ Permission
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id'], 'role_has_permissions_pk');
        });

        // Pivot: Permission ↔ SecuredResource
        Schema::create($tableNames['permission_has_resources'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('secured_resource_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign('secured_resource_id')
                ->references('id')
                ->on($tableNames['secured_resources'])
                ->cascadeOnDelete();

            $table->primary(['permission_id', 'secured_resource_id'], 'permission_has_resources_pk');
        });
    }

    public function down(): void
    {
        $tableNames = config('rolepermissionmanager.tables');

        Schema::dropIfExists($tableNames['permission_has_resources']);
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
    }
};
