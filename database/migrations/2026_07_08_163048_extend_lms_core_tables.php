<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'short_description')) {
                $table->string('short_description')->nullable()->after('description');
            }
            if (! Schema::hasColumn('courses', 'thumbnail')) {
                $table->string('thumbnail')->nullable()->after('short_description');
            }
            if (! Schema::hasColumn('courses', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('thumbnail');
            }
            if (! Schema::hasColumn('courses', 'is_premium_only')) {
                $table->boolean('is_premium_only')->default(false)->after('price');
            }
            if (! Schema::hasColumn('courses', 'difficulty')) {
                $table->string('difficulty')->nullable()->after('is_premium_only');
            }
            if (! Schema::hasColumn('courses', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('difficulty');
            }
            if (! Schema::hasColumn('courses', 'requirements')) {
                $table->json('requirements')->nullable()->after('duration_minutes');
            }
            if (! Schema::hasColumn('courses', 'objectives')) {
                $table->json('objectives')->nullable()->after('requirements');
            }
            if (! Schema::hasColumn('courses', 'faq')) {
                $table->json('faq')->nullable()->after('objectives');
            }
            if (! Schema::hasColumn('courses', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('courses', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('submitted_at');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'status')) {
                $table->string('status')->default('active')->after('course_id');
            }
            if (! Schema::hasColumn('enrollments', 'current_lesson_id')) {
                $table->foreignId('current_lesson_id')->nullable()->after('progress')->constrained('lessons')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'certificate_eligible')) {
                $table->boolean('certificate_eligible')->default(false)->after('completed_at');
            }
            if (! Schema::hasColumn('enrollments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('certificate_eligible');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'meeting_link')) {
                $table->string('meeting_link')->nullable()->after('response_note');
            }
        });

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_audit_logs', 'role')) {
                $table->string('role')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('admin_audit_logs', 'module')) {
                $table->string('module')->nullable()->after('action');
            }
            if (! Schema::hasColumn('admin_audit_logs', 'description')) {
                $table->text('description')->nullable()->after('module');
            }
            if (! Schema::hasColumn('admin_audit_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('description');
            }
            if (! Schema::hasColumn('admin_audit_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }
            if (! Schema::hasColumn('admin_audit_logs', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan');
            $table->string('status')->default('active');
            $table->string('provider')->default('manual');
            $table->string('provider_subscription_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XOF');
            $table->string('provider')->default('manual');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('completed');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            foreach (['role', 'module', 'description', 'old_values', 'new_values', 'user_agent'] as $col) {
                if (Schema::hasColumn('admin_audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'current_lesson_id')) {
                $table->dropForeign(['current_lesson_id']);
            }
            foreach (['status', 'current_lesson_id', 'certificate_eligible', 'cancelled_at'] as $col) {
                if (Schema::hasColumn('enrollments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            foreach (['short_description', 'thumbnail', 'price', 'is_premium_only', 'difficulty', 'duration_minutes', 'requirements', 'objectives', 'faq', 'submitted_at', 'published_at'] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
