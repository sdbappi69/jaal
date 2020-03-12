<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if(!Schema::hasTable('order_products'))
      {
        Schema::create('order_products', function (Blueprint $table) {
          $table->increments('id')->unsigned();
          $table->integer('order_id')->unsigned();
          $table->integer('product_id')->unsigned();
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
        Schema::drop('order_products');
    }
}