<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Invoice;

// Search for Alice
$student = Student::where('name', 'like', '%Alice Oliveira%')->first();

if (!$student) {
    echo "Student 'Alice' not found.\n";
    exit;
}

echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Pending Fees Count (Attribute): {$student->pending_fees_count}\n\n";

echo "--- MONTHLY FEES (Pending/Overdue) ---\n";
$pendingFees = MonthlyFee::where('student_id', $student->id)
    ->whereIn('status', ['pending', 'overdue'])
    ->get();

foreach ($pendingFees as $fee) {
    echo "ID: {$fee->id} | Month: {$fee->month}/{$fee->year} | Amount: {$fee->amount} | Status: {$fee->status}\n";
}

echo "\n--- INVOICES ---\n";
$invoices = Invoice::where('student_id', $student->id)->get();

foreach ($invoices as $inv) {
    echo "ID: {$inv->id} | Status: {$inv->status} | Total: {$inv->total}\n";
    foreach ($inv->items as $item) {
        echo "   - Item: {$item->description} ({$item->type})\n";
    }
}
