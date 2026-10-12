<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('tool_categories')->nullOnDelete();
            $table->string('visibility', 20)->default('private'); // 'private', 'unlisted', 'public'
            $table->string('status', 20)->default('draft'); // 'draft', 'published', 'archived'
            $table->string('current_version', 30)->default('v1.0.0');
            $table->foreignId('fork_of_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('forks_count')->default(0);
            $table->unsignedBigInteger('storage_bytes')->default(0);
            $table->unsignedInteger('file_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'status', 'created_at']);
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // e.g. "index.html", "css/style.css"
            $table->string('filename');
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->string('hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'path']);
        });

        Schema::create('project_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('version_tag', 50);
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('archive_path');
            $table->unsignedInteger('file_count')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'version_tag']);
        });

        Schema::create('project_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('project_tag_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('project_tags')->cascadeOnDelete();

            $table->unique(['project_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tag_map');
        Schema::dropIfExists('project_tags');
        Schema::dropIfExists('project_snapshots');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('projects');
    }
};
