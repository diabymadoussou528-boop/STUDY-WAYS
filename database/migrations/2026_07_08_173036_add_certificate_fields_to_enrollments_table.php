<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'certificate_number')) {
                $table->string('certificate_number')->nullable()->unique()->after('certificate_eligible');
            }
            if (! Schema::hasColumn('enrollments', 'certificate_issued_at')) {
                $table->timestamp('certificate_issued_at')->nullable()->after('certificate_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            foreach (['certificate_number', 'certificate_issued_at'] as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
