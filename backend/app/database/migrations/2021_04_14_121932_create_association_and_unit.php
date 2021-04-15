<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssociationAndUnit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('address')->unique();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('association_id')->index();
            $table->foreign('association_id')->references('id')->on('associations')->onDelete('cascade');
            $table->string('address')->unique();
        });

        Schema::create('unit_user', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->index();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('association_user', function (Blueprint $table) {
            $table->unsignedBigInteger('association_id')->index();
            $table->foreign('association_id')->references('id')->on('associations')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('unit_user');
        Schema::dropIfExists('association_user');
        Schema::dropIfExists('units');
        Schema::dropIfExists('associations');
    }
}
