<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_id', 100)->unique();
            $table->string('client_secret', 255)->nullable();
            $table->string('name');
            $table->text('redirect_uris')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });

        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_id', 100);
            $table->string('code', 100)->unique();
            $table->text('scopes')->nullable();
            $table->string('code_challenge', 128)->nullable();
            $table->string('code_challenge_method', 20)->nullable();
            $table->text('redirect_uri')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_id', 'revoked']);
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_id', 100);
            $table->string('token_hash', 64)->unique();
            $table->text('scopes')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'revoked']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('content');
            $table->string('status', 20)->default('approved'); // 'approved', 'flagged', 'hidden'
            $table->timestamps();

            $table->index(['project_id', 'status', 'created_at']);
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // e.g. "mcp:file:write", "project:publish", "auth:login"
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
        Schema::dropIfExists('oauth_clients');
    }
};
