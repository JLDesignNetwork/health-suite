<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->date('date');

            // Body
            $table->decimal('weight', total: 6, places: 2)->nullable();
            $table->decimal('neck', total: 5, places: 2)->nullable();
            $table->decimal('waist', total: 5, places: 2)->nullable();
            $table->decimal('hip', total: 5, places: 2)->nullable();

            // Vitals
            $table->unsignedSmallInteger('systolic')->nullable();
            $table->unsignedSmallInteger('diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();

            // Activity
            $table->decimal('water_intake_l', total: 4, places: 2)->nullable();
            $table->unsignedSmallInteger('exercise_minutes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
