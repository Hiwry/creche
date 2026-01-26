<?php

use App\Models\AttendanceLog;
use App\Models\ClassModel;
use Carbon\Carbon;

// Find the log for 2026-01-21 with check_in 06:00
$log = AttendanceLog::where('date', '2026-01-21')
    ->where('check_in', 'like', '06:%')
    ->with('classModel')
    ->first();

if ($log) {
    echo "Found Log ID: " . $log->id . "\n";
    echo "Student ID: " . $log->student_id . "\n";
    echo "Date: " . $log->date->format('Y-m-d') . "\n";
    echo "Check In (DB): " . $log->getRawOriginal('check_in') . "\n";
    echo "Check Out (DB): " . $log->getRawOriginal('check_out') . "\n";
    echo "Expected Start (DB): " . $log->getRawOriginal('expected_start') . "\n";
    echo "Expected End (DB): " . $log->getRawOriginal('expected_end') . "\n";
    echo "Extra Minutes (DB): " . $log->extra_minutes . "\n";
    
    echo "Class Start: " . $log->classModel->start_time . "\n";
    echo "Class End: " . $log->classModel->end_time . "\n";

    // Recalculate manually
    $calculated = $log->calculateExtraMinutes(10); // assuming 10 tolerance
    echo "Calculated Extra Minutes (Tolerance 10): " . $calculated . "\n";
    
    // Check specific times
    $dateStr = $log->date->format('Y-m-d');
    $checkInStr = $log->check_in ? $log->check_in->format('H:i') : null;
    $expectedStartStr = $log->expected_start ? $log->expected_start->format('H:i') : null;
    
    echo "Parsed Date: $dateStr\n";
    echo "Parsed Check In: $checkInStr\n";
    echo "Parsed Expected Start: $expectedStartStr\n";
    
    if ($checkInStr && $expectedStartStr) {
         $checkIn = Carbon::parse($dateStr . ' ' . $checkInStr);
         $expectedStart = Carbon::parse($dateStr . ' ' . $expectedStartStr);
         echo "Diff (Start): " . $checkIn->diffInMinutes($expectedStart, false) . " mins\n";
    }

} else {
    echo "Log not found for 2026-01-21 with 06:00 check_in.\n";
    // List all logs for that day to see what's there
    $logs = AttendanceLog::where('date', '2026-01-21')->get();
    foreach ($logs as $l) {
        echo "Log ID: {$l->id}, CheckIn: {$l->check_in}\n";
    }
}
