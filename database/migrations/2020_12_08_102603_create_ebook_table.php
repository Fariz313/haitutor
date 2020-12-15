<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebook', function (Blueprint $table) {
            $table->id();
            $table->integer('id_category');
            $table->integer('id_publisher');
            $table->string('item_code')->unique();
            $table->string('isbn')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->integer('type')->default(1);
            $table->integer('jenjang')->default(1);
            $table->integer('price')->default(0);
            $table->integer('is_published')->default(1);
            $table->integer('is_deleted')->default(0);
            $table->text('description')->nullable();
            $table->string('front_cover')->nullable();
            $table->string('back_cover')->nullable();
            $table->string('content_file');
            $table->float('rating')->default(0);
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
        Schema::dropIfExists('ebook');
    }
}
