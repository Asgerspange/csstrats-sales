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
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('promocode')->unique();
            $table->string('email')->unique();
            $table->string('bank_account')->nullable();
            $table->string('iban')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('min_payout_amount', 10, 2)->default(50.00);
            $table->decimal('total_earned', 10, 2)->default(0.00);
            $table->decimal('total_paid', 10, 2)->default(0.00);
            $table->string('status')->default('active');
            $table->date('last_update')->nullable();
            $table->string('access_token')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
