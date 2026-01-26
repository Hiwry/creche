<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_health', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('health_plan_name')->nullable();
            $table->string('health_plan_number')->nullable();
            $table->date('health_plan_validity')->nullable();
            $table->text('medications')->nullable();
            $table->text('medication_schedule')->nullable();
            $table->text('allergies')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_health');
    }
};
