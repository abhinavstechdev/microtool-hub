<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('client_id', 500)->change();
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->string('client_id', 500)->change();
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->string('client_id', 500)->change();
        });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('client_id', 100)->change();
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->string('client_id', 100)->change();
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->string('client_id', 100)->change();
        });
    }
};
