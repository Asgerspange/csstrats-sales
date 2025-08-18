<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('email_id')->references('id')->on('emails')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_attachments');
    }
};
