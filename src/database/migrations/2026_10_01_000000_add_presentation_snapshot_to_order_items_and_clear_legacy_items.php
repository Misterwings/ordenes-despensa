<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRESENTACIONES = ['BOL', 'CAJA', 'CAN', 'TARR', 'UND', 'KG'];

    public function up(): void
    {
        if (! Schema::hasColumn('order_items', 'presentacion')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('presentacion')->nullable()->after('item_id');
            });
        }

        DB::transaction(function () {
            // Preserve what existing orders display before clearing legacy catalog values.
            DB::table('order_items')
                ->join('items', 'items.id', '=', 'order_items.item_id')
                ->whereNull('order_items.presentacion')
                ->update(['order_items.presentacion' => DB::raw('items.presentacion')]);

            DB::table('items')
                ->whereIn(DB::raw('UPPER(TRIM(presentacion))'), self::PRESENTACIONES)
                ->update(['presentacion' => DB::raw('UPPER(TRIM(presentacion))')]);

            DB::table('items')
                ->where(function ($query) {
                    $query->whereNull('presentacion')
                        ->orWhereNotIn(DB::raw('UPPER(TRIM(presentacion))'), self::PRESENTACIONES);
                })
                ->update(['presentacion' => '']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'presentacion')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('presentacion');
            });
        }
    }
};
