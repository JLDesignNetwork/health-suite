<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifestyle_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->unique()->constrained()->cascadeOnDelete();

            // Diet
            $table->string('dietary_regimen')->nullable();
            $table->text('food_restrictions')->nullable();
            $table->string('caffeine_intake')->nullable();

            // Activity & sleep
            $table->text('physical_activity')->nullable();
            $table->decimal('sleep_hours', total: 3, places: 1)->nullable();
            $table->string('sleep_notes')->nullable();

            // Substance use
            $table->string('tobacco_use')->nullable();
            $table->string('alcohol_use')->nullable();
            $table->text('substance_notes')->nullable();

            // Goals
            $table->text('wellness_goals')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifestyle_profiles');
    }
};
