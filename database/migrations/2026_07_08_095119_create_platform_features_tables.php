<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('courses', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }

            if (! Schema::hasColumn('courses', 'status')) {
                $table->string('status')->default('published')->after('description');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('users', 'premium_plan')) {
                $table->string('premium_plan')->nullable()->after('is_premium');
            }
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('status')->default('pending');
            $table->text('message')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_action_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_requests');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('enrollments');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'premium_plan')) {
                $table->dropColumn('premium_plan');
            }
            if (Schema::hasColumn('users', 'is_premium')) {
                $table->dropColumn('is_premium');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('courses', 'slug')) {
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('courses', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
