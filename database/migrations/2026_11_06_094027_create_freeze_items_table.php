<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What customers leave in the freezer. It costs nothing until it is
     * picked up; then the price is set and a debit links back to it.
     */
    public function up(): void
    {
        Schema::create('freeze_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('description', 150);
            $table->unsignedSmallInteger('quantity')->nullable();
            // DATETIME, not TIMESTAMP: MariaDB would move a required TIMESTAMP on every update.
            $table->dateTime('stored_at');
            $table->dateTime('picked_up_at')->nullable();
            $table->foreignId('picked_up_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('form_token')->nullable()->unique();
            $table->timestamps();

            $table->index(['picked_up_at', 'stored_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('freeze_item_id')->references('id')->on('freeze_items')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['freeze_item_id']);
        });

        Schema::dropIfExists('freeze_items');
    }
};
