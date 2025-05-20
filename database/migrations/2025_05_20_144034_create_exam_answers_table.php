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
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained('exam_questions')->onDelete('cascade');
            $table->text('answer');
            $table->boolean('is_correct')->nullable();
            $table->integer('marks_awarded')->default(0);
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            // Each attempt can have only one answer per question
            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_answers');
    }
};