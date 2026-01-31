<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassModel;
use App\Models\AttendanceLog;
use App\Models\Setting;
use Carbon\Carbon;

class AutoRegisterAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-register {date? : The date to process (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically registers attendance for students who missed check-in/out';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateStr = $this->argument('date') ?? Carbon::yesterday()->toDateString();
        $date = Carbon::parse($dateStr);
        $dayOfWeek = strtolower($date->format('l'));

        $this->info("Processing attendance for: {$dateStr} ({$dayOfWeek})");

        // Get all active classes that have sessions on this day
        $classes = ClassModel::active()
            ->whereJsonContains('days_of_week', $dayOfWeek)
            ->with(['enrollments.student'])
            ->get();

        $processedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($classes as $classModel) {
            $this->info("Processing Class: {$classModel->name}");

            foreach ($classModel->enrollments as $enrollment) {
                if (!$enrollment->student->is_active) {
                    continue; // Skip inactive students
                }

                $studentId = $enrollment->student_id;
                $student = $enrollment->student;
                $expectedStart = $student->start_time ?? $classModel->start_time;
                $expectedEnd = $student->end_time ?? $classModel->end_time;

                // Check for existing log
                $log = AttendanceLog::where('date', $dateStr)
                    ->where('student_id', $studentId)
                    ->where('class_id', $classModel->id)
                    ->first();

                if (!$log) {
                    // Scenario 1: No record at all -> Create full attendance
                    AttendanceLog::create([
                        'student_id' => $studentId,
                        'class_id' => $classModel->id,
                        'date' => $dateStr,
                        'check_in' => $expectedStart,
                        'check_out' => $expectedEnd,
                        'expected_start' => $expectedStart,
                        'expected_end' => $expectedEnd,
                        'registered_by' => 1, // System admin or bot user
                    ]);
                    $createdCount++;
                    $this->line("  [NEW] Created full attendance for {$enrollment->student->name}");
                } else {
                    // Scenario 2: Check-in exists but no check-out
                    if ($log->check_in && !$log->check_out) {
                        $log->check_out = $expectedEnd;
                        if (!$log->expected_start || !$log->expected_end) {
                            $log->expected_start = $expectedStart;
                            $log->expected_end = $expectedEnd;
                        }
                        
                        // Recalculate extras if needed
                        $tolerance = Setting::getExtraHourTolerance();
                        $hourlyRate = Setting::getExtraHourRate();
                        $log->extra_minutes = $log->calculateExtraMinutes($tolerance);
                        $log->extra_charge = $log->calculateExtraCharge($hourlyRate, $tolerance);
                        
                        $log->save();
                        $updatedCount++;
                        $this->line("  [UPD] Closed open check-in for {$enrollment->student->name}");
                    }
                }
                $processedCount++;
            }
        }

        $this->info("Done! Processed: {$processedCount}. Created: {$createdCount}. Updated: {$updatedCount}.");
    }
}
