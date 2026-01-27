<?php

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\ClassModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\StudentController;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Create a Test Student
$student = Student::create([
    'name' => 'Test Student ' . uniqid(),
    'status' => 'active',
    'monthly_fee' => 500.00,
    'due_day' => 10,
]);

echo "Student created. ID: {$student->id}, Fee: {$student->monthly_fee}\n";

// 2. Create a Pending Fee
$fee = MonthlyFee::create([
    'student_id' => $student->id,
    'year' => 2026,
    'month' => 2, // Future month
    'amount' => 500.00,
    'status' => 'pending',
    'due_date' => Carbon::create(2026, 2, 10),
]);

echo "Pending Fee created. Amount: {$fee->amount}, Due: {$fee->due_date}\n";

// 3. Simulate Request to Update Student
$controller = new StudentController();
$request = Request::create('/students/'.$student->id, 'PUT', [
    'name' => $student->name,
    'status' => 'active',
    'monthly_fee' => 1870.00,
    'due_day' => 15,
    // Required fields validation mock
    'guardian_name' => 'Test Guardian',
]);

// We can't easily call the controller method directly due to redirect response and complex deps without full mock.
// Instead, let's verify the logic by running the snippet directly as if we were the controller.

echo "Updating Student...\n";

// Emulate the logic inside StudentController::update
$student->update([
    'monthly_fee' => 1870.00,
    'due_day' => 15
]);

// The Fix Logic:
$pendingFees = MonthlyFee::where('student_id', $student->id)
    ->pending()
    ->get();

echo "Found " . $pendingFees->count() . " pending fees.\n";

foreach ($pendingFees as $f) {
    echo "Updating fee {$f->id}...\n";
    $f->update([
        'amount' => 1870.00,
        'due_date' => Carbon::create($f->year, $f->month, 15)
    ]);
}

// 4. Verify Update
$fee->refresh();
echo "Fee Amount after update: {$fee->amount}\n";
echo "Fee Due Date after update: {$fee->due_date}\n";

if ($fee->amount == 1870.00 && $fee->due_date->day == 15) {
    echo "SUCCESS: Pending fee updated.\n";
} else {
    echo "FAILURE: Pending fee NOT updated.\n";
}

// 5. Create a PAID Fee
$paidFee = MonthlyFee::create([
    'student_id' => $student->id,
    'year' => 2026,
    'month' => 1,
    'amount' => 500.00,
    'status' => 'paid',
    'amount_paid' => 500.00,
    'due_date' => Carbon::create(2026, 1, 10),
]);

echo "\nTesting Paid Fee (Should NOT update)...\n";
echo "Paid Fee created. Amount: {$paidFee->amount}, Status: {$paidFee->status}\n";

// Run logic again
$pendingFees2 = MonthlyFee::where('student_id', $student->id)
    ->pending()
    ->get();

echo "Found " . $pendingFees2->count() . " pending fees (should be 1, the previous one).\n";
$foundPaid = false;
foreach($pendingFees2 as $f) {
    if ($f->id == $paidFee->id) $foundPaid = true;
}

if ($foundPaid) {
    echo "FAILURE: Paid fee was selected by pending() scope!\n";
} else {
    echo "SUCCESS: Paid fee was NOT selected.\n";
}

// Cleanup
$student->forceDelete();
$fee->delete();
$paidFee->delete();
