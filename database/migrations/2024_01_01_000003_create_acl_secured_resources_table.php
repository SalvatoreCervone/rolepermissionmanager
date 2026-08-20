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
            $table->string('identifier')->unique()->comment('Route name or METHOD:uri signature');
            $table->string('controller_action')->nullable()->comment('Full controller@method for traceability');
            $table->string('method', 10)->index()->comment('HTTP verb: GET, POST, PUT, PATCH, DELETE');
            $table->string('uri')->comment('Route URI pattern e.g. api/v1/users/{id}');
            $table->boolean('is_public')->default(false)->comment('If true, bypasses all auth checks');
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
