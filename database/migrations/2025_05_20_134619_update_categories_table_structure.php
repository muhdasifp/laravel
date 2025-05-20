<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            // First rename existing columns
            $table->renameColumn('category_name', 'name');
            $table->renameColumn('image', 'thumbnail_url');
            
            // Add new columns
            $table->string('slug')->unique()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->boolean('is_active')->default(true)->after('is_svg');
        });

        // Generate slugs for existing data
        DB::table('categories')->get()->each(function ($category) {
            DB::table('categories')
                ->where('id', $category->id)
                ->update(['slug' => Str::slug($category->name)]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['slug', 'description', 'is_active']);
            
            // Rename columns back to original
            $table->renameColumn('name', 'category_name');
            $table->renameColumn('thumbnail_url', 'image');
        });
    }
};