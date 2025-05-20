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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('thumbnail_is_svg')->default(false);
            $table->string('audio_file')->nullable();
            $table->string('video_file')->nullable();
            $table->string('sample_file')->nullable();
            $table->integer('listen_duration')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_live')->default(false);
            $table->json('pdf_files')->nullable();
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
        Schema::dropIfExists('modules');
    }
};