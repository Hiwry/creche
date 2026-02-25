<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Invoice;

$alice = Student::where('name', 'like', '%Alice Oliveira%')->first();

if (!$alice) {
    echo "Alice not found.\n";
    exit;
}

echo "STUDENT INFO:\n";
echo "ID: {$alice->id}\n";
echo "Name: {$alice->name}\n";
echo "Monthly Fee (DB): " . var_export($alice->monthly_fee, true) . "\n";
echo "Status: {$alice->status}\n";

echo "\nENROLLMENTS:\n";
$enrollments = $alice->enrollments;
foreach ($enrollments as $e) {
    echo "ID: {$e->id} | ClassID: {$e->class_id} | Status: {$e->status} | Start: {$e->start_date} | End: {$e->end_date}\n";
}

echo "\nACTIVE ENROLLMENTS RELATIONSHIP:\n";
foreach ($alice->activeEnrollments as $e) {
    echo "ID: {$e->id} | ClassID: {$e->class_id}\n";
}

echo "\nMONTHLY FEES (01/2026):\n";
$fees = MonthlyFee::where('student_id', $alice->id)->where('year', 2026)->where('month', 1)->get();
foreach ($fees as $f) {
    echo "ID: {$f->id} | Amount: {$f->amount} | Status: {$f->status}\n";
}

echo "\nINVOICE (01/2026):\n";
$invoice = Invoice::where('student_id', $alice->id)->where('year', 2026)->where('month', 1)->first();
if ($invoice) {
    echo "ID: {$invoice->id} | Total: {$invoice->total} | Status: {$invoice->status}\n";
    echo "ITEMS:\n";
    foreach ($invoice->items as $item) {
        echo " - Type: {$item->type} | Desc: {$item->description} | Total: {$item->total}\n";
    }
} else {
    echo "Invoice NOT found.\n";
}
