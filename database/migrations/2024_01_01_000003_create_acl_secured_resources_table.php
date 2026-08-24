<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('rolepermissionmanager.tables');

        Schema::create($tableNames['secured_resources'], function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique()->comment('Route name, class@method, or custom resource identifier');
            $table->string('type', 20)->default('route')->index()->comment('Resource type: route or custom');
            $table->string('description')->nullable()->comment('Human readable description');
            $table->string('controller_action')->nullable()->comment('Full controller@method or class@method for traceability');
            $table->string('source_file')->nullable()->comment('Route definition source file e.g. routes/web.php');
            $table->string('method', 10)->nullable()->index()->comment('HTTP verb: GET, POST, PUT, PATCH, DELETE (null for custom)');
            $table->string('uri')->nullable()->comment('Route URI pattern e.g. api/v1/users/{id} (null for custom)');
            $table->boolean('is_public')->default(false)->comment('If true, bypasses all auth checks');
            $table->boolean('is_super_admin_only')->default(false)->comment('If true, accessible exclusively by Super Admin');
            $table->enum('operator', ['OR', 'AND'])->default('OR')->comment('Logic for evaluating multiple permissions');
            $table->boolean('is_deprecated')->default(false)->comment('Flagged when route no longer exists in code');
            $table->timestamps();

            $table->index(['method', 'uri']);
        });
    }

    public function down(): void
    {
        $tableNames = config('rolepermissionmanager.tables');
        Schema::dropIfExists($tableNames['secured_resources']);
    }
};

