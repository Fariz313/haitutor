<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FileAsk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_ask', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id');
            $table->string('file_url');
            $table->enum('ask_type',['ask','answer']);
            $table->enum('file_type',['image','document']);
            $table->date('deleted_at');
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
        Schema::dropIfExists('file_ask');

    }
}
