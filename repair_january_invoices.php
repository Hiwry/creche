<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;

function makeDueDate($year, $month, $day) {
    $base = Carbon::create($year, $month, 1);
    $day = max(1, min($day, $base->daysInMonth));
    return Carbon::create($year, $month, $day);
}

echo "Starting ROBUST repair for January 2026 invoices...\n";

$year = 2026;
$month = 1;

// 1. Get all draft invoices for Jan 2026
$invoices = Invoice::where('year', $year)
    ->where('month', $month)
    ->where('status', 'draft')
    ->get();

echo "Found {$invoices->count()} draft invoices for Jan 2026.\n";

$fixedInvoices = 0;
$createdFees = 0;

foreach ($invoices as $invoice) {
    $student = $invoice->student;
    if (!$student) continue;

    echo "Processing Student: {$student->name} (ID: {$student->id})...\n";

    // ENSURE MonthlyFees exist if student has a fee profile
    if ($student->monthly_fee > 0) {
        $enrollments = $student->activeEnrollments;
        if ($enrollments->count() > 0) {
            foreach ($enrollments as $enrollment) {
                $fee = MonthlyFee::where([
                    'student_id' => $student->id,
                    'class_id' => $enrollment->class_id,
                    'year' => $year,
                    'month' => $month
                ])->first();

                if (!$fee) {
                    echo " - Creating missing MonthlyFee for class {$enrollment->class_id}\n";
                    MonthlyFee::create([
                        'student_id' => $student->id,
                        'class_id' => $enrollment->class_id,
                        'year' => $year,
                        'month' => $month,
                        'amount' => $student->monthly_fee,
                        'status' => 'pending',
                        'due_date' => makeDueDate($year, $month, (int) ($student->due_day ?? Setting::getPaymentDueDay())),
                    ]);
                    $createdFees++;
                } else if ($fee->amount <= 0) {
                    echo " - Updating zero-value MonthlyFee ID: {$fee->id}\n";
                    $fee->update(['amount' => $student->monthly_fee]);
                }
            }
        } else {
            echo " - WARNING: Student has fee profile but NO ACTIVE ENROLLMENTS.\n";
            // Optional: create a generic monthly fee if no class?
            // For now, let's just log it.
        }
    }

    // Now recalculate the invoice items
    echo " - Recalculating invoice items...\n";
    $invoice->items()->delete();

    // Re-add items logic
    $monthlyFeesData = MonthlyFee::where('student_id', $student->id)
        ->forMonth($year, $month)
        ->get();

    foreach ($monthlyFeesData as $fee) {
        if ($fee->remaining_amount > 0) {
            $invoice->addItem(
                'monthly_fee',
                "Mensalidade {$fee->reference}" . ($fee->classModel ? " - {$fee->classModel->name}" : ''),
                1,
                $fee->remaining_amount
            );
        }
    }

    // Material and Extras
    $materialFee = \App\Models\MaterialFee::where('student_id', $student->id)->forYear($year)->pending()->first();
    if ($materialFee && $materialFee->remaining_amount > 0) {
        $invoice->addItem('material_fee', "Taxa de Material {$year}", 1, $materialFee->remaining_amount);
    }

    $extraHours = \App\Models\AttendanceLog::where('student_id', $student->id)->forMonth($year, $month)->where('extra_charge', '>', 0)->get();
    if ($extraHours->sum('extra_charge') > 0) {
        $invoice->addItem('extra_hours', "Horas extras", 1, $extraHours->sum('extra_charge'));
    }

    $invoice->recalculateTotals();
    echo " - New Total: R$ " . number_format($invoice->total, 2, ',', '.') . "\n";
    $fixedInvoices++;
}

echo "\nRepair completed!\n";
echo "Created/Updated MonthlyFees: {$createdFees}\n";
echo "Fixed Invoices: {$fixedInvoices}\n";
