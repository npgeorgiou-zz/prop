<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration {

    public function up() {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->timestamps();

            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    public function down() {
        Schema::dropIfExists('invitations');
    }
}
