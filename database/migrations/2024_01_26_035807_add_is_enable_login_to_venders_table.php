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
        if (!Schema::hasColumn('venders', 'is_enable_login')) {
            Schema::table('venders', function (Blueprint $table) {
                $table->integer('is_enable_login')->default(1)->after('is_active');   
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venders', function (Blueprint $table) {
            //
        });
    }
};
