<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetainersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retainers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('retainer_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('send_date')->nullable();
            $table->integer('category_id');
            $table->integer('status')->default('0');
            $table->integer('discount_apply')->default('0');
            $table->integer('converted_invoice_id')->default('0');
            $table->integer('is_convert')->default('0');
            $table->integer('created_by')->default('0');
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
        Schema::dropIfExists('retainers');
    }
}
