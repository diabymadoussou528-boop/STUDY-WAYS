<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_modules')) {
            Schema::create('course_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'module_id')) {
                $table->foreignId('module_id')->nullable()->after('course_id')->constrained('course_modules')->nullOnDelete();
            }
            if (! Schema::hasColumn('lessons', 'content')) {
                $table->text('content')->nullable()->after('title');
            }
            if (! Schema::hasColumn('lessons', 'lesson_type')) {
                $table->string('lesson_type')->default('video')->after('content');
            }
            if (! Schema::hasColumn('lessons', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('lesson_type');
            }
            if (! Schema::hasColumn('lessons', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('duration_seconds');
            }
            if (! Schema::hasColumn('lessons', 'resource_url')) {
                $table->string('resource_url')->nullable()->after('video_url');
            }
            if (! Schema::hasColumn('lessons', 'resource_path')) {
                $table->string('resource_path')->nullable()->after('resource_url');
            }
            if (! Schema::hasColumn('lessons', 'is_preview')) {
                $table->boolean('is_preview')->default(false)->after('resource_path');
            }
        });

        if (Schema::hasColumn('lessons', 'video_url')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->string('video_url')->nullable()->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('avatar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }
        });

        Schema::table('lessons', function (Blueprint $table) {
            foreach (['module_id', 'content', 'lesson_type', 'duration_seconds', 'sort_order', 'resource_url', 'resource_path', 'is_preview'] as $col) {
                if (Schema::hasColumn('lessons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('course_modules');
    }
};
