<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->decimal('quantity_on_hand', total: 8, places: 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropColumn('quantity_on_hand');
        });
    }
};
