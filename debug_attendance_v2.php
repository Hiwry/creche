<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\AttendanceLog;
use App\Models\ClassModel;
use Carbon\Carbon;

// Find the log for 2026-01-21 with check_in 06:00
// Try to match partial time string since exact format might vary slightly
$log = AttendanceLog::where('date', '2026-01-21')
    ->get()
    ->filter(function($l) {
        return $l->check_in && $l->check_in->format('H:i') === '06:00';
    })
    ->first();

if ($log) {
    echo "Found Log ID: " . $log->id . "\n";
    echo "Student: " . ($log->student ? $log->student->name : 'Unknown') . "\n";
    
    // Use raw attributes to see exactly what is in DB
    $raw = $log->getAttributes();
    echo "Check In (Raw): " . ($raw['check_in'] ?? 'NULL') . "\n";
    echo "Check Out (Raw): " . ($raw['check_out'] ?? 'NULL') . "\n";
    echo "Expected Start (Raw): " . ($raw['expected_start'] ?? 'NULL') . "\n";
    echo "Expected End (Raw): " . ($raw['expected_end'] ?? 'NULL') . "\n";
    
    // Check relation
    if ($log->classModel) {
        echo "Class: " . $log->classModel->name . "\n";
        echo "Class Start Time: " . $log->classModel->start_time . "\n";
        echo "Class End Time: " . $log->classModel->end_time . "\n";
    }

    echo "Extra Minutes (DB): " . $log->extra_minutes . "\n";
    
    // Recalculate
    $calculated = $log->calculateExtraMinutes(10);
    echo "Calculated Extra Minutes (Tolerance 10): " . $calculated . "\n";
    
    // Debug calculation
    $dateStr = $log->date->format('Y-m-d');
    $checkInStr = $log->check_in->format('H:i');
    $checkOutStr = $log->check_out->format('H:i');
    $expStartStr = $log->expected_start ? $log->expected_start->format('H:i') : 'NULL';
    $expEndStr = $log->expected_end ? $log->expected_end->format('H:i') : 'NULL';
    
    echo "Comparing: In $checkInStr vs ExpStart $expStartStr\n";
    echo "Comparing: Out $checkOutStr vs ExpEnd $expEndStr\n";

} else {
    echo "Log not found for 2026-01-21 with 06:00 check_in.\n";
    $all = AttendanceLog::where('date', '2026-01-21')->get();
    echo "Logs found for date: " . $all->count() . "\n";
    foreach($all as $l) {
        echo " - ID {$l->id}: In " . ($l->check_in ? $l->check_in->format('H:i') : 'NULL') . "\n";
    }
}
