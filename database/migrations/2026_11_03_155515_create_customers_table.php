<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('phone', 15)->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_walk_in')->default(false);
            $table->timestamps();
        });

        // The one shared customer for walk-ins who pay on the spot. Created
        // here, not in a seeder, so it exists on every database.
        DB::table('customers')->insert([
            'name' => 'زبون عابر',
            'is_walk_in' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
