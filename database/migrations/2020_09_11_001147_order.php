<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Order extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('package_id');
            $table->integer('method_id')->nullable();
            $table->string('invoice');
            $table->string('va_number')->nullable();
            $table->string('proof')->nullable();
            $table->string('detail')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('pos')->nullable();
            $table->integer('type_code')->nullable();    
            $table->enum('status',['pending','failed','completed']);
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
        Schema::dropIfExists('order');
    }
}
