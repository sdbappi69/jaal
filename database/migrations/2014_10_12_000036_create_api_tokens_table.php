<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if(!Schema::hasTable('api_tokens'))
      {
        Schema::create('api_tokens', function (Blueprint $table) {
          $table->increments('id')->unsigned();
          $table->string('title');
          $table->integer('user_id')->unsigned();
          $table->string('permissions', 30);
          $table->string('token');
          $table->timestamps();
        });
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('api_tokens');
    }
}
