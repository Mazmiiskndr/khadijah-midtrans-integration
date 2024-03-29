<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id('cart_id');
            $table->uuid('cart_uid')->unique(); // Added uid column
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('color',50);
            $table->string('size',50);
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->foreign('product_id')
                    ->references('product_id')
                    ->on('product')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            $table->foreign('customer_id')
                    ->references('id')
                    ->on('customer')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
