<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TutorDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tutor_doc', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('tutor_id');
            $table->string('file');
            $table->enum('type',['ijazah','skhu','certificate','ktp','no_rekening','other']);
            $table->enum('status',['unverified','verified','pending']);
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
        Schema::dropIfExists('tutor_doc');
    }
}
