<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'payments', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->date('date');
            $table->decimal('amount',15,2)->default('0.00');
            $table->integer('account_id')->nullable();
            $table->integer('vender_id')->nullable();
            $table->text('description');
            $table->integer('category_id')->nullable();
            $table->string('recurring')->nullable();
            $table->integer('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('add_receipt')->nullable();
            $table->integer('created_by')->default('0');
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
        Schema::dropIfExists('payments');
    }
}
