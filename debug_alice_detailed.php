<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Invoice;
use App\Models\MonthlyFee;

// Search for Alice
$student = Student::where('name', 'like', '%Alice Oliveira%')->first();

if (!$student) {
    echo "Student 'Alice' not found.\n";
    exit;
}

echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Pending Fees Count: {$student->pending_fees_count}\n";

$fees = MonthlyFee::where('student_id', $student->id)->get();

echo "\n--- Monthly Fees ---\n";
foreach ($fees as $fee) {
    $invoice = Invoice::where('student_id', $student->id)
        ->where('year', $fee->year)
        ->where('month', $fee->month)
        ->where('status', '!=', 'cancelled')
        ->first();

    $invoiceStatus = $invoice ? $invoice->status : 'NO INVOICE';
    $invoiceId = $invoice ? $invoice->id : '-';

    echo "{$fee->month}/{$fee->year}: Fee Status=[{$fee->status}] | Invoice ID=[{$invoiceId}] Status=[{$invoiceStatus}]\n";
}
