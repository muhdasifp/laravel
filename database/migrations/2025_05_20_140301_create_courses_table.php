<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('thumbnail_url')->nullable();
            $table->boolean('thumbnail_is_svg')->default(false); 
            $table->string('cover_image_url')->nullable();
            $table->boolean('cover_image_is_svg')->default(false);
            $table->foreignId('category_id')->constrained();
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('instructor_id')->constrained();
            $table->enum('level', ['Beginner', 'Intermediate', 'Advanced', 'All Levels']);
            $table->string('language');
            $table->date('completed_date')->nullable();
            $table->integer('duration_hours');
            $table->integer('total_modules');
            $table->integer('total_lessons');
            $table->integer('total_videos')->default(0);
            $table->integer('total_audios')->default(0);
            $table->integer('total_pdfs')->default(0);
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->boolean('is_free')->default(false);
            $table->boolean('is_live')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
};