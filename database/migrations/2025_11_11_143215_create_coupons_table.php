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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('coupon_id')->unique();
            $table->string('name')->nullable();
            $table->string('amount_off')->nullable();
            $table->string('percent_off')->nullable();
            $table->string('currency')->nullable();
            $table->string('duration')->nullable();
            $table->integer('duration_in_months')->nullable();
            $table->boolean('valid')->default(true);
            $table->timestamp('redeem_by')->nullable();
            $table->integer('max_redemptions')->nullable();
            $table->integer('times_redeemed')->default(0);
            $table->json('promotion_codes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
