<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The redesign leads with photos of the café itself. Only replaces the old placeholder photos, never the owner's own. */
    public function up(): void
    {
        $swap = [
            'hero' => ['seed-matcha-crunch.jpg', 'seed-shop-counter-5d1fceff3a826f86.jpg'],
            'cover' => ['seed-cover.jpg', 'seed-shop-seating-0e008f5a74a5049e.jpg'],
        ];
        foreach ($swap as $key => [$old, $new]) {
            DB::table('settings')->where('key', $key)->where('value', $old)->update(['value' => $new]);
        }
    }

    public function down(): void {}
};
