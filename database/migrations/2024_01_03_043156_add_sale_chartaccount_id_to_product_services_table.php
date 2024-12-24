<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_services', function (Blueprint $table) {
            $table->integer('sale_chartaccount_id')->default('0')->after('type');
            $table->integer('expense_chartaccount_id')->default('0')->after('sale_chartaccount_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_services', function (Blueprint $table) {
            //
        });
    }
};
