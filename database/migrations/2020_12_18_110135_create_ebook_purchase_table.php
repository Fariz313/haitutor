<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbookPurchaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebook_purchase', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('ebook_id');
            $table->integer('method_id')->default(0);
            $table->text('payment_information')->nullable();
            $table->string('invoice')->nullable();
            $table->text('detail')->nullable();
            $table->integer('amount');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('ebook_purchase');
    }
}
