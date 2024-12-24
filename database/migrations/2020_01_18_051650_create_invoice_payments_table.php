<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'invoice_payments', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->integer('invoice_id');
            $table->date('date');
            $table->decimal('amount', 15, 2)->default('0.00');
            $table->integer('account_id');
            $table->integer('payment_method');
            $table->string('order_id')->nullable();
            $table->string('currency')->nullable();
            $table->string('txn_id')->nullable();
            $table->string('payment_type')->default('Manually');
            $table->string('receipt')->nullable();
            $table->string('reference');
            $table->string('add_receipt')->nullable();
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
        Schema::dropIfExists('invoice_payments');
    }
}
