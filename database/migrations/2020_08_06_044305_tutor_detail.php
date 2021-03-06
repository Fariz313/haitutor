<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TutorDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tutor_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->text('biography')->nullable();
            $table->enum('status',['unverified','verified','pending']);
            $table->string('nik')->nullable();
            $table->string('no_rekening')->nullable();
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
        Schema::dropIfExists('tutor_detail');
    }
}
