<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->unique()->after('provider_payment_id');
            }
            if (! Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->unique()->after('transaction_id');
            }
            if (! Schema::hasColumn('payments', 'refund_status')) {
                $table->string('refund_status')->default('none')->after('status');
            }
            if (! Schema::hasColumn('payments', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('refund_status');
            }
            if (! Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_amount');
            }
            if (! Schema::hasColumn('payments', 'failure_reason')) {
                $table->string('failure_reason')->nullable()->after('refunded_at');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            foreach ([
                'meta_title' => 'string',
                'meta_description' => 'text',
                'meta_keywords' => 'string',
                'og_image' => 'string',
                'canonical_url' => 'string',
            ] as $column => $type) {
                if (! Schema::hasColumn('courses', $column)) {
                    if ($type === 'text') {
                        $table->text($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                }
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'verification_token')) {
                $table->string('verification_token', 64)->nullable()->unique()->after('certificate_number');
            }
        });

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            foreach (['browser', 'operating_system', 'device'] as $column) {
                if (! Schema::hasColumn('admin_audit_logs', $column)) {
                    $table->string($column)->nullable()->after('user_agent');
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_streak')) {
                $table->unsignedSmallInteger('current_streak')->default(0)->after('premium_plan');
            }
            if (! Schema::hasColumn('users', 'longest_streak')) {
                $table->unsignedSmallInteger('longest_streak')->default(0)->after('current_streak');
            }
            if (! Schema::hasColumn('users', 'last_study_date')) {
                $table->date('last_study_date')->nullable()->after('longest_streak');
            }
            if (! Schema::hasColumn('users', 'total_study_minutes')) {
                $table->unsignedInteger('total_study_minutes')->default(0)->after('last_study_date');
            }
        });

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('number')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('XOF');
                $table->string('status')->default('paid');
                $table->string('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quizzes')) {
            Schema::create('quizzes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('time_limit_minutes')->nullable();
                $table->unsignedTinyInteger('max_attempts')->default(3);
                $table->unsignedTinyInteger('passing_score')->default(70);
                $table->boolean('randomize_questions')->default(false);
                $table->boolean('show_feedback')->default(true);
                $table->boolean('is_published')->default(false);
                $table->timestamp('available_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->text('question');
                $table->json('options')->nullable();
                $table->text('correct_answer')->nullable();
                $table->unsignedSmallInteger('points')->default(1);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('score')->default(0);
                $table->unsignedTinyInteger('percentage')->default(0);
                $table->boolean('passed')->default(false);
                $table->string('status')->default('in_progress');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('time_spent_seconds')->default(0);
                $table->timestamps();

                $table->index(['quiz_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('quiz_attempt_answers')) {
            Schema::create('quiz_attempt_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
                $table->text('answer')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->unsignedSmallInteger('points_awarded')->default(0);
                $table->text('feedback')->nullable();
                $table->timestamp('graded_at')->nullable();
                $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lesson_completions')) {
            Schema::create('lesson_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('time_spent_seconds')->default(0);
                $table->timestamp('completed_at');
                $table->timestamps();

                $table->unique(['user_id', 'lesson_id']);
            });
        }

        if (! Schema::hasTable('learning_sessions')) {
            Schema::create('learning_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'started_at']);
            });
        }

        if (! Schema::hasTable('search_histories')) {
            Schema::create('search_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('query');
                $table->string('scope')->default('all');
                $table->unsignedSmallInteger('results_count')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
        Schema::dropIfExists('learning_sessions');
        Schema::dropIfExists('lesson_completions');
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('invoices');

        Schema::table('users', function (Blueprint $table) {
            foreach (['current_streak', 'longest_streak', 'last_study_date', 'total_study_minutes'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            foreach (['browser', 'operating_system', 'device'] as $col) {
                if (Schema::hasColumn('admin_audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'verification_token')) {
                $table->dropColumn('verification_token');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            foreach (['meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url'] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            foreach (['transaction_id', 'receipt_number', 'refund_status', 'refund_amount', 'refunded_at', 'failure_reason'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
