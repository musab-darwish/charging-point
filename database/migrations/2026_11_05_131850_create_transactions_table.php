<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per movement on a customer's account: a debit (عليه) or a
     * credit (دفعة). Rows are never edited or deleted; a mistake is fixed
     * with a reversing row. restrictOnDelete keeps every row's customer,
     * item and recorder in place.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->string('category', 20)->nullable();
            $table->foreignId('price_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description', 150)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamp('occurred_at');
            $table->string('method', 10)->nullable();
            $table->unsignedBigInteger('payment_account_id')->nullable(); // Foreign key added with payment_accounts (step 8)
            $table->string('reference', 60)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('reverses_id')->nullable()->unique()->constrained('transactions')->restrictOnDelete();
            $table->unsignedBigInteger('freeze_item_id')->nullable(); // Foreign key added with freeze_items (step 9)
            $table->uuid('form_token')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
