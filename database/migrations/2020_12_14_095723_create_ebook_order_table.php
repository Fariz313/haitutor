<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbookOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebook_order', function (Blueprint $table) {
            $table->id();
            $table->string('invoice');
            $table->integer('id_customer');
            $table->integer('id_marketing')->nullable();
            $table->integer('id_publisher')->nullable();
            $table->integer('gross_price')->default(0);
            $table->integer('net_price');
            $table->integer('validity_month')->default(0);
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('ebook_order');
    }
}
