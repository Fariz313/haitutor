<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisbursementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disbursement', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('amount');
            $table->integer('status');
            $table->string('information')->nullable();
            $table->datetime('accepted_at')->nullable();
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
        Schema::dropIfExists('disbursement');
    }
}
