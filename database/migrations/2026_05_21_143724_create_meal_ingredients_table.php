<?php

use App\Models\Ingredient;
use App\Models\Meal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Meal::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Ingredient::class)->constrained()->cascadeOnDelete();
            $table->decimal('amount_used', total: 8, places: 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_ingredients');
    }
};
