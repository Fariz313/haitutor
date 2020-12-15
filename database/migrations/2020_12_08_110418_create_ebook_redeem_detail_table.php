<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbookRedeemDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebook_redeem_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('id_redeem');
            $table->integer('id_ebook');
            $table->string('redeem_code');
            $table->integer('redeem_used');
            $table->integer('redeem_amount');
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
        Schema::dropIfExists('ebook_redeem_detail');
    }
}
