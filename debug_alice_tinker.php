<?php

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Invoice;

// Search for Alice
$student = Student::where('name', 'like', '%Alice Oliveira%')->first();

if (!$student) {
    echo "Student 'Alice' not found." . PHP_EOL;
    return;
}

echo "Student: {$student->name} (ID: {$student->id})" . PHP_EOL;
echo "Pending Fees Count (Attribute): {$student->pending_fees_count}" . PHP_EOL . PHP_EOL;

echo "--- MONTHLY FEES (Pending/Overdue) ---" . PHP_EOL;
$pendingFees = MonthlyFee::where('student_id', $student->id)
    ->whereIn('status', ['pending', 'overdue'])
    ->get();

foreach ($pendingFees as $fee) {
    echo "ID: {$fee->id} | Month: {$fee->month}/{$fee->year} | Amount: {$fee->amount} | Status: {$fee->status}" . PHP_EOL;
}

echo PHP_EOL . "--- INVOICES ---" . PHP_EOL;
$invoices = Invoice::where('student_id', $student->id)->get();

foreach ($invoices as $inv) {
    echo "ID: {$inv->id} | Status: {$inv->status} | Total: {$inv->total}" . PHP_EOL;
    foreach ($inv->items as $item) {
        echo "   - Item: {$item->description} ({$item->type})" . PHP_EOL;
    }
}
