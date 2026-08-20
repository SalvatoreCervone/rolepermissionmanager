<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('rolepermissionmanager.tables.scanner_rules', 'acl_scanner_rules');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('exclude'); // 'exclude' or 'include'
            $table->string('target', 20)->default('name');   // 'name' (Route Name) or 'prefix' (URI Prefix)
            $table->string('pattern');                       // e.g. 'workbench.*', 'api/v1/public/*', 'reports.export'
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['target', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('rolepermissionmanager.tables.scanner_rules', 'acl_scanner_rules');
        Schema::dropIfExists($tableName);
    }
};
