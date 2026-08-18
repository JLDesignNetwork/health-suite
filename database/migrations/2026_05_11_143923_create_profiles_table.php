<?php

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->unique()->constrained()->cascadeOnDelete();

            // Biometrics
            $table->enum('gender', array_column(Gender::cases(), 'value'));
            $table->date('dob');
            $table->decimal('height_cm', total: 5, places: 2);

            // Baseline measurements
            $table->decimal('baseline_weight', total: 6, places: 2);
            $table->decimal('baseline_neck', total: 5, places: 2);
            $table->decimal('baseline_waist', total: 5, places: 2);
            $table->decimal('baseline_hip', total: 5, places: 2)->nullable();

            // Baseline physiology
            $table->unsignedSmallInteger('baseline_pulse');
            $table->unsignedSmallInteger('baseline_systolic');
            $table->unsignedSmallInteger('baseline_diastolic');

            // Goals
            $table->decimal('target_weight', total: 6, places: 2)->nullable();
            $table->unsignedInteger('daily_calorie_goal')->nullable();
            $table->decimal('daily_water_goal', total: 4, places: 2)->nullable();
            $table->unsignedSmallInteger('weekly_exercise_goal')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
