<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_info', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->unique()->constrained()->cascadeOnDelete();

            // Medical identifiers
            $table->string('blood_type')->nullable();
            $table->string('pronouns')->nullable();

            // Emergency contact 1
            $table->string('emergency_contact_1_name')->nullable();
            $table->string('emergency_contact_1_relationship')->nullable();
            $table->string('emergency_contact_1_phone')->nullable();

            // Emergency contact 2
            $table->string('emergency_contact_2_name')->nullable();
            $table->string('emergency_contact_2_relationship')->nullable();
            $table->string('emergency_contact_2_phone')->nullable();

            // Primary care
            $table->string('primary_care_physician')->nullable();
            $table->string('pcp_phone')->nullable();

            // Insurance
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_member_id')->nullable();
            $table->string('insurance_group_number')->nullable();
            $table->string('insurance_phone')->nullable();

            // Notes
            $table->text('patient_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_info');
    }
};
