<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'users',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('email');
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('type', 20);
                $table->string('avatar', 100)->nullable();
                $table->string('lang', 100);
                $table->string('mode', 10)->default('light');
                $table->integer('created_by')->default(0);
                $table->integer('delete_status')->default(1);
                $table->integer('is_active')->default(1);
                $table->rememberToken();
                $table->datetime('last_login_at')->nullable();
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
        Schema::dropIfExists('users');
    }
}
