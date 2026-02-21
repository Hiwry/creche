<?php

namespace App\Services\Financial;

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class FeeReconciler
{
    /**
     * Reconcile fees for a specific student.
     * 
     * @param Student $student
     * @return array Result summary
     */
    public function reconcileStudent(Student $student): array
    {
        $results = [
            'fixed' => 0,
            'processed' => 0,
            'details' => []
        ];

        // Get all pending or overdue monthly fees
        $pendingFees = MonthlyFee::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->get();

        foreach ($pendingFees as $fee) {
            $results['processed']++;

            // Check if there is an ACTIVE invoice for this month/year
            $invoice = Invoice::where('student_id', $student->id)
                ->where('year', $fee->year)
                ->where('month', $fee->month)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($invoice) {
                // If invoice exists and is PAID, but fee is PENDING, force fee to PAID
                if ($invoice->status === 'paid' && $fee->status !== 'paid') {
                     $fee->status = 'paid';
                     $fee->amount_paid = $fee->net_amount; // Align amount
                     $fee->save();
                     
                     $results['fixed']++;
                     $results['details'][] = "Fee {$fee->month}/{$fee->year} marked as PAID (Synced with Invoice #{$invoice->id})";
                }
            } else {
                // If there is no active invoice, but the fee is pending, it's an orphan/mismatch.
                
                // Verify if there are payments attached to this fee directly?
                if ($fee->amount_paid >= $fee->net_amount) {
                     $fee->status = 'paid';
                     $fee->save();
                     $results['fixed']++;
                     $results['details'][] = "Fee {$fee->month}/{$fee->year} marked as PAID (Found sufficient payments)";
                     continue;
                }

                // Do not force paid status without payment evidence.
                // Keep fee open so it can be billed correctly on next invoice generation/recalculation.
                $results['details'][] = "Fee {$fee->month}/{$fee->year} kept as {$fee->status} (No active invoice and no payment evidence)";
            }
        }

        return $results;
    }
}
