<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'thumbnail_url')) {
                $table->text('thumbnail_url')->nullable()->after('thumbnail');
            }

            if (! Schema::hasColumn('courses', 'thumbnail_drive_id')) {
                $table->string('thumbnail_drive_id')->nullable()->after('thumbnail_url');
            }

            if (! Schema::hasColumn('courses', 'video_drive_id')) {
                $table->string('video_drive_id')->nullable()->after('google_drive_video_url');
            }

            if (! Schema::hasColumn('courses', 'upload_status')) {
                $table->string('upload_status')->default('pending')->after('video_drive_id');
            }

            if (! Schema::hasIndex('courses', 'courses_upload_status_index')) {
                $table->index('upload_status', 'courses_upload_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach (['upload_status', 'video_drive_id', 'thumbnail_drive_id', 'thumbnail_url'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
