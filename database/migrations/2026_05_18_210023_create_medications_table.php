<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            // Identity
            $table->string('name');
            $table->string('category');
            $table->string('form');

            // Dosing
            $table->string('dosage');
            $table->string('frequency');
            $table->string('timing')->nullable();

            // Context
            $table->text('reason')->nullable();
            $table->string('prescribing_doctor')->nullable();
            $table->date('start_date')->nullable();
            $table->string('status')->default('Active');

            // Identification
            $table->string('pill_color')->nullable();
            $table->string('pill_shape')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
