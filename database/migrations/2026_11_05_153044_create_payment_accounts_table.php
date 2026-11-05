<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The accounts customers transfer to (a bank account or a wallet app),
     * and the link from a transfer payment to the account it arrived on.
     */
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('holder_name', 100);
            $table->string('number', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The same number can be on two apps, but not twice on one.
            $table->unique(['name', 'number']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
        });

        Schema::dropIfExists('payment_accounts');
    }
};
