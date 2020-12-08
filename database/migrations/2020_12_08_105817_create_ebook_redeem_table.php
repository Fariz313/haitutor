<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbookRedeemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebook_redeem', function (Blueprint $table) {
            $table->id();
            $table->string('invoice');
            $table->integer('id_customer');
            $table->integer('gross_price');
            $table->integer('net_price');
            $table->integer('validity_month');
            $table->integer('status');
            $table->integer('is_deleted');
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
        Schema::dropIfExists('ebook_redeem');
    }
}
