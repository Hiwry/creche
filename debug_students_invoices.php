<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Student;
use App\Models\Invoice;

$s2 = Student::find(2);
echo "Student 2: " . ($s2 ? $s2->name : 'Not Found') . "\n";

$s3 = Student::find(3);
echo "Student 3: " . ($s3 ? $s3->name : 'Not Found') . "\n";

echo "\nInvoices for Student 3 (Julia):\n";
$invoicesS3 = Invoice::where('student_id', 3)->get();
foreach($invoicesS3 as $inv) {
    echo " - ID: {$inv->id}, Number: {$inv->invoice_number}, Month: {$inv->month}/{$inv->year}\n";
    // Check items
    foreach($inv->items as $item) {
        echo "   * {$item->description}: {$item->total}\n";
    }
}

echo "\nInvoices for Student 2:\n";
$invoicesS2 = Invoice::where('student_id', 2)->get();
foreach($invoicesS2 as $inv) {
    echo " - ID: {$inv->id}, Number: {$inv->invoice_number}, Month: {$inv->month}/{$inv->year}\n";
}
