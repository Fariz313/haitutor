<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomVcTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_vc', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('channel_name');
            $table->integer('user_id');
            $table->integer('tutor_id');
            $table->date('deleted_at');
            $table->date('expired_at');
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
        Schema::dropIfExists('room_vc');
    }
}
