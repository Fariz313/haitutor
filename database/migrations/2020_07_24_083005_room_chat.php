<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RoomChat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_chat', function (Blueprint $table) {
            $table->id();
            $table->string('room_key')->unique();
            $table->integer('user_id');
            $table->integer('tutor_id');
            $table->enum('chat_type',['standart','langganan']);
            $table->enum('status',['open','closed']);
            $table->string('last_message')->nullable();
            $table->integer('last_sender')->nullable();
            $table->date('last_message_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->date('deleted_at')->nullable();
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
        Schema::dropIfExists('room_chat');
    }
}
