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
            $table->integer('package_id')->default(0);
            $table->integer('method_id')->nullable();
            $table->string('invoice')->nullable();
            $table->text('va_number')->nullable();
            $table->string('proof')->nullable();
            $table->text('detail')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('pos')->nullable();
            $table->integer('type_code')->nullable();
            $table->enum('status',['pending','failed','completed']);
            $table->integer('is_deleted')->default(0);
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
