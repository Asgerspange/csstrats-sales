<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->unsignedBigInteger('sender_id');
            $table->enum('status', ['draft', 'sent', 'failed'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraint
        });
    }

    public function down()
    {
        Schema::dropIfExists('emails');
    }
};
