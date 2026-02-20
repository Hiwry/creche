<?php

use App\Services\Financial\FeeReconciler;
use App\Models\Student;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force bulk reconcile logic test
echo "Testing Bulk Reconciliation Logic...\n";

$reconciler = new FeeReconciler();

// Find Alice again
$alice = Student::where('name', 'like', '%Alice Oliveira%')->first();
if ($alice) {
    echo "Alice found. Current Pending Fees: {$alice->pending_fees_count}\n";
    $results = $reconciler->reconcileStudent($alice);
    echo "Alice Reconciliation Result: Fixed {$results['fixed']} fees.\n";
    foreach($results['details'] as $detail) {
        echo " - $detail\n";
    }
    $alice->refresh();
    echo "Alice Post-Sync Pending Fees: {$alice->pending_fees_count}\n";
}

echo "Done.\n";
