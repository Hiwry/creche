<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Invoice;
use App\Models\AttendanceLog;
use App\Models\Student;

// 1. Find Invoice #1 (implied 1/2026) -> checking if it exists
$invoice = Invoice::where('year', 2026)->where('month', 1)->first();

if (!$invoice) {
    echo "Invoice not found for 01/2026.\n";
    // Try to find by student name "Julia Lima"
    $student = Student::where('name', 'like', '%Julia Lima%')->first();
    if ($student) {
        echo "Found Student: {$student->name} (ID: {$student->id})\n";
        $invoice = Invoice::where('student_id', $student->id)->latest()->first();
    }
}

if ($invoice) {
    echo "Found Invoice ID: {$invoice->id}\n";
    echo "Student ID: {$invoice->student_id}\n";
    echo "Status: {$invoice->status}\n";
    echo "Year/Month: {$invoice->year}/{$invoice->month}\n";
    
    echo "Current Items:\n";
    foreach ($invoice->items as $item) {
        echo " - {$item->type}: {$item->description} (Amount: {$item->total})\n";
    }

    // 2. Check Attendance Logs for this student/month
    echo "\nChecking Attendance Logs for Student {$invoice->student_id} in {$invoice->year}-{$invoice->month}...\n";
    
    $logs = AttendanceLog::where('student_id', $invoice->student_id)
        ->whereYear('date', $invoice->year)
        ->whereMonth('date', $invoice->month)
        ->get();
        
    echo "Found {$logs->count()} logs.\n";
    
    $totalMinutes = 0;
    $totalCharge = 0;
    
    foreach ($logs as $log) {
        echo " - Date: {$log->date->format('Y-m-d')}, Extra Min: {$log->extra_minutes}, Charge: {$log->extra_charge}\n";
        $totalMinutes += $log->extra_minutes;
        $totalCharge += $log->extra_charge;
    }
    
    echo "Total Calculated: {$totalMinutes} min, R$ {$totalCharge}\n";
    
    // 3. Test Query used in Controller
    $controllerQueryLogs = AttendanceLog::where('student_id', $invoice->student_id)
            ->forMonth($invoice->year, $invoice->month)
            ->where('extra_charge', '>', 0)
            ->get();
            
    echo "Controller Query found {$controllerQueryLogs->count()} logs with charge > 0.\n";

} else {
    echo "No invoice found to debug.\n";
}
