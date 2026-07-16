<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('normalized_name')->nullable()->after('name');
            $table->unique('normalized_name');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('language', 50)->nullable()->after('difficulty');
        });

        $categories = DB::table('categories')->select('id', 'name')->get();

        foreach ($categories as $category) {
            $normalized = Str::of($category->name)
                ->lower()
                ->ascii()
                ->replaceMatches('/\s+/u', ' ')
                ->trim()
                ->toString();

            DB::table('categories')
                ->where('id', $category->id)
                ->update(['normalized_name' => $normalized !== '' ? $normalized : 'category-'.$category->id]);
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('language');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['normalized_name']);
            $table->dropColumn('normalized_name');
        });
    }
};
