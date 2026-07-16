<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'creator_id')) {
                $table->foreignId('creator_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('courses', 'teacher_id')) {
                $table->foreignId('teacher_id')
                    ->nullable()
                    ->after('creator_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('courses', 'approval_status')) {
                $table->string('approval_status')
                    ->default('not_required')
                    ->after('status');
            }

            if (! Schema::hasColumn('courses', 'google_drive_thumbnail_id')) {
                $table->string('google_drive_thumbnail_id')->nullable()->after('video_path');
            }

            if (! Schema::hasColumn('courses', 'google_drive_video_id')) {
                $table->string('google_drive_video_id')->nullable()->after('google_drive_thumbnail_id');
            }

            if (! Schema::hasColumn('courses', 'google_drive_thumbnail_url')) {
                $table->text('google_drive_thumbnail_url')->nullable()->after('google_drive_video_id');
            }

            if (! Schema::hasColumn('courses', 'google_drive_video_url')) {
                $table->text('google_drive_video_url')->nullable()->after('google_drive_thumbnail_url');
            }

            if (! Schema::hasColumn('courses', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('published_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('courses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach ([
                'approved_at',
                'approved_by',
                'google_drive_video_url',
                'google_drive_thumbnail_url',
                'google_drive_video_id',
                'google_drive_thumbnail_id',
                'approval_status',
                'teacher_id',
                'creator_id',
            ] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
