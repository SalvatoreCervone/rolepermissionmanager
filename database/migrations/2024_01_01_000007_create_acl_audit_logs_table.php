<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('rolepermissionmanager.tables.audit_logs', 'acl_audit_logs');

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('action', 64)->index();
                $table->string('target_type', 64)->nullable()->index();
                $table->string('target_identifier')->nullable();
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        $tableName = config('rolepermissionmanager.tables.audit_logs', 'acl_audit_logs');
        Schema::dropIfExists($tableName);
    }
};
