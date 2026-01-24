<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Invoice;
use App\Models\MonthlyFee;
use App\Models\MaterialFee;
use App\Models\AttendanceLog;

// Find all draft invoices for 01/2026
$invoices = Invoice::where('year', 2026)
    ->where('month', 1)
    ->where('status', 'draft')
    ->get();

echo "Found {$invoices->count()} draft invoices to recalculate.\n";

foreach ($invoices as $invoice) {
    echo "Recalculating Invoice #{$invoice->invoice_number} (Student ID: {$invoice->student_id})...\n";
    
    // 1. Remove items
    $invoice->items()->delete();
    
    // 2. Re-add Item Logic (Inline from Controller)
    
    // Monthly
    $monthlyFees = MonthlyFee::where('student_id', $invoice->student_id)
        ->forMonth($invoice->year, $invoice->month)
        ->get();
        
    foreach ($monthlyFees as $fee) {
        if ($fee->remaining_amount > 0) {
            $invoice->addItem(
                'monthly_fee',
                "Mensalidade {$fee->reference}" . ($fee->classModel ? " - {$fee->classModel->name}" : ''),
                1,
                $fee->remaining_amount
            );
            echo " - Added Monthly Fee: {$fee->remaining_amount}\n";
        }
    }
    
    // Material
    $materialFee = MaterialFee::where('student_id', $invoice->student_id)
        ->forYear($invoice->year)
        ->pending()
        ->first();
        
    if ($materialFee && $materialFee->remaining_amount > 0) {
        $invoice->addItem(
            'material_fee',
            "Taxa de Material {$invoice->year}",
            1,
            $materialFee->remaining_amount
        );
        echo " - Added Material Fee: {$materialFee->remaining_amount}\n";
    }
    
    // Extra Hours
    $extraHours = AttendanceLog::where('student_id', $invoice->student_id)
        ->forMonth($invoice->year, $invoice->month)
        ->where('extra_charge', '>', 0)
        ->get();
        
    $totalExtraMinutes = $extraHours->sum('extra_minutes');
    $totalExtraCharge = $extraHours->sum('extra_charge');
    
    if ($totalExtraCharge > 0) {
        if ($totalExtraMinutes > 0) {
            $hours = floor($totalExtraMinutes / 60);
            $minutes = $totalExtraMinutes % 60;
            $description = "Horas extras ({$hours}h{$minutes}min)";
        } else {
            $description = "Adicional de Horas Extras";
        }
        
        $invoice->addItem(
            'extra_hours',
            $description,
            1,
            $totalExtraCharge
        );
        echo " - Added Extra Hours: {$totalExtraCharge}\n";
    } else {
        echo " - No Extra Hours found (Charge > 0).\n";
        // Debug check
        $logCount = AttendanceLog::where('student_id', $invoice->student_id)
            ->forMonth($invoice->year, $invoice->month)->count();
        echo "   (Debug: Found {$logCount} total logs for this student/month)\n";
    }
    
    $invoice->recalculateTotals();
    $invoice->save();
    echo "Done. New Total: {$invoice->total}\n\n";
}
