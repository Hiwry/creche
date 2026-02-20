<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\AttendanceLog;
use App\Models\Setting;

echo "Starting recalculation...\n";

$logs = AttendanceLog::whereNotNull('check_in')
    ->whereNotNull('check_out')
    ->get();

$tolerance = Setting::getExtraHourTolerance();
$hourlyRate = Setting::getExtraHourRate();

$count = 0;
foreach ($logs as $log) {
    if (!$log->expected_start || !$log->expected_end) {
        $log->load('classModel');
        if ($log->classModel) {
            $log->expected_start = $log->classModel->start_time;
            $log->expected_end = $log->classModel->end_time;
            // Don't save yet, updateExtraCalculations will save
        }
    }

    $oldMinutes = $log->extra_minutes;
    $log->updateExtraCalculations($hourlyRate, $tolerance);
    
    if ($log->extra_minutes != $oldMinutes) {
        echo "Updated Log ID {$log->id}: {$oldMinutes} -> {$log->extra_minutes} min\n";
        $count++;
    }
}

echo "Recalculation complete. Updated {$count} logs.\n";
