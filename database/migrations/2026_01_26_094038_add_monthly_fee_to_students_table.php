<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('monthly_fee', 10, 2)->nullable()->after('status');
        });

        // Optional: Initialize existing students with their class fee
        $students = DB::table('students')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->where('enrollments.status', 'active')
            ->select('students.id', 'classes.monthly_fee')
            ->get();

        foreach ($students as $student) {
            DB::table('students')
                ->where('id', $student->id)
                ->update(['monthly_fee' => $student->monthly_fee]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('monthly_fee');
        });
    }
};
