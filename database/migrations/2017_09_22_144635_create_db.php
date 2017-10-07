<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDb extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('categories', function(Blueprint $table) {
            $table->increments('category_id');
            $table->string('name', 45);
            $table->string('description', 45);
            $table->string('image', 256)->nullable();
        });

        Schema::create('tags', function(Blueprint $table) {
            $table->increments('tag_id');
            $table->string('name', 32);
            $table->integer('category_id')->unsigned();
            
            $table->foreign('category_id')->references('category_id')->on('categories');
        });

        Schema::create('quizzes', function(Blueprint $table) {
            $table->increments('quiz_id');
            $table->integer('category_id')->unsigned();
            $table->string('name', 32);
            $table->timestamp('created')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('attempts')->unsigned()->default(0);
            $table->integer('questions')->unsigned()->default(40);

            $table->foreign('category_id')->references('category_id')->on('categories');
        });

        Schema::create('questions', function(Blueprint $table) {
            $table->increments('question_id');
            $table->integer('quiz_id')->unsigned();
            $table->string('question', 128);
            $table->string('image', 256)->nullable();
            $table->string('right_answer', 128);
            $table->string('wrong_answer_1', 128);
            $table->string('wrong_answer_2', 128);
            $table->string('wrong_answer_3', 128);
            $table->string('tags', 256)->nullable();
            
            $table->foreign('quiz_id')->references('quiz_id')->on('quizzes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('tags');
        Schema::drop('questions');        
        Schema::drop('quizzes');
        Schema::drop('categories');        
    }
}
