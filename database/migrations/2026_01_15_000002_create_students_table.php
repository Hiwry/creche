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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('guardians')->onDelete('cascade');
            $table->string('name');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F', 'O'])->nullable();
            $table->string('photo')->nullable();
            $table->text('observations')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('authorized_pickups')->nullable(); // pessoas autorizadas a buscar
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
