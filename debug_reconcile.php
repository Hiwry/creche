<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Services\Financial\FeeReconciler;

// Search for Alice
$student = Student::where('name', 'like', '%Alice Oliveira%')->first();

if (!$student) {
    echo "Student 'Alice' not found.\n";
    exit;
}

echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Pending Fees BEFORE: {$student->pending_fees_count}\n";

$reconciler = new FeeReconciler();
$results = $reconciler->reconcileStudent($student);

echo "Fixed: {$results['fixed']}\n";
echo "Processed: {$results['processed']}\n";
foreach($results['details'] as $detail) {
    echo " - $detail\n";
}

// Reload student to check updated count
$student->refresh(); // Pending fees count is attribute, not column, but underlying relation changed.
echo "Pending Fees AFTER: {$student->pending_fees_count}\n";
