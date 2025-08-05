<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAzureFieldsToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'azure_id')) {
                $table->string('azure_id')->unique()->nullable();
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (! Schema::hasColumn('users', 'access_token')) {
                $table->string('access_token', 1024)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['azure_id', 'avatar', 'access_token']);
        });
    }
}