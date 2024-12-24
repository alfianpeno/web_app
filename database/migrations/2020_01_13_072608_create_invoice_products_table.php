<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_products',function (Blueprint $table) 
        {
                $table->bigIncrements('id');
                $table->integer('invoice_id');
                $table->integer('product_id');
                $table->decimal('quantity', 15, 2)->default('0.00');
                $table->decimal('tax', 15, 2)->default('0.00');
                $table->decimal('discount', 15, 2)->default('0.00');
                $table->decimal('price', 15, 2)->default('0.00');
                $table->text('description')->nullable();
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_products');
    }
}
