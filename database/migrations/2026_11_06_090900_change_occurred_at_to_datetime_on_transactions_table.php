<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a movement happened must never change. As a TIMESTAMP, MariaDB
     * (with explicit_defaults_for_timestamp off, as in XAMPP) quietly gave
     * the column ON UPDATE CURRENT_TIMESTAMP, so marking a transfer
     * "تأكدت" moved it to the time of the click. A DATETIME has no such rule.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('occurred_at')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('occurred_at')->change();
        });
    }
};
