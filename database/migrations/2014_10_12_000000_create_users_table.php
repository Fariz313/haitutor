<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->date('birth_date');
            $table->string('photo')->nullable();
            $table->integer('role');
            $table->string('jenjang')->nullable();
            $table->string('contact');
            $table->integer('company_id')->nullable();
            $table->string('address');
            $table->enum('status', ['unverified', 'verified']);
            $table->integer('isRestricted')->default(0);
            $table->integer('balance')->default(0);
            $table->string('firebase_token')->nullable();

            $table->rememberToken();
            $table->integer("is_deleted");
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
        Schema::dropIfExists('users');
    }
}
