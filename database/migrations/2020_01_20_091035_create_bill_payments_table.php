<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('bill_id');
            $table->date('date')->nullable();
            $table->decimal('amount', 15, 2)->default('0.00');
            $table->integer('account_id')->nullable();
            $table->integer('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('add_receipt')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('bill_payments');
    }
}
