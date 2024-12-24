<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetainerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retainer_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('retainer_id');
            $table->date('date');
            $table->decimal('amount', 15, 2)->default('0.00');
            $table->integer('account_id');
            $table->integer('payment_method');
            $table->string('receipt')->nullable();
            $table->string('payment_type')->default('Manually');
            $table->string('txn_id')->nullable();
            $table->string('currency')->nullable();
            $table->string('order_id')->nullable();
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
        Schema::dropIfExists('retainer_payments');
    }
}
