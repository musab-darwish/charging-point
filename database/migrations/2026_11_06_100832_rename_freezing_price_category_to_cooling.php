<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The price list's "freezing" items (cakes, water, cream) are a day's
     * cooling at a fixed price: they become "cooling". "freezing" stays for
     * the freezer page, where a charge links back to its freeze item.
     */
    public function up(): void
    {
        DB::table('price_items')->where('category', 'freezing')->update(['category' => 'cooling']);

        DB::table('transactions')
            ->where('category', 'freezing')
            ->whereNull('freeze_item_id')
            ->update(['category' => 'cooling']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('price_items')->where('category', 'cooling')->update(['category' => 'freezing']);
        DB::table('transactions')->where('category', 'cooling')->update(['category' => 'freezing']);
    }
};
