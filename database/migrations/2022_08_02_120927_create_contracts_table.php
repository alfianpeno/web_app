<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('customer')->default(0);
            $table->string('subject')->nullable();
            $table->decimal('value', 15, 2)->default(0.00);
            $table->integer('type')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('edit_status')->default('pending');
            $table->text('description')->nullable();
            $table->longText('notes')->nullable();
            $table->longText('customer_signature')->nullable();
            $table->longText('company_signature')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('contracts');
    }
}
