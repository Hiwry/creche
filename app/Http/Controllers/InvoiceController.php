<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\MaterialFee;
use App\Models\AttendanceLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with('student');
        
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }
        
        $invoices = $query->latest()->paginate(20);
        
        return view('invoices.index', compact('invoices'));
    }
    
    /**
     * Generate invoice for a student.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        $student = Student::findOrFail($request->student_id);
        $year = $request->year;
        $month = $request->month;
        
        // Check if invoice already exists
        $existing = Invoice::where('student_id', $student->id)
            ->forMonth($year, $month)
            ->first();
            
        if ($existing) {
            return redirect()->route('invoices.show', $existing)
                ->with('info', 'Fatura já existe para este mês.');
        }
        
        // Create invoice
        $invoice = Invoice::create([
            'student_id' => $student->id,
            'year' => $year,
            'month' => $month,
            'status' => 'draft',
            'due_date' => Carbon::create($year, $month, $student->due_day ?? Setting::getPaymentDueDay()),
        ]);
        
        $this->calculateItems($invoice);
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Fatura gerada com sucesso!');
    }

    /**
     * Recalculate invoice items (for draft invoices).
     */
    public function recalculate(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Apenas faturas em rascunho podem ser recalculadas.');
        }

        // Remove existing items
        $invoice->items()->delete();

        // Recalculate
        $this->calculateItems($invoice);

        // Update totals
        $invoice->recalculateTotals();
        $invoice->save();

        return back()->with('success', 'Fatura recalculada com sucesso!');
    }

    /**
     * Helper to calculate and add items to an invoice.
     */
    private function calculateItems(Invoice $invoice)
    {
        // Add monthly fees (ensure they exist first if student has a fee set)
        $student = $invoice->student;
        if ($student && $student->monthly_fee > 0) {
            foreach ($student->activeEnrollments as $enrollment) {
                MonthlyFee::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'class_id' => $enrollment->class_id,
                        'year' => $invoice->year,
                        'month' => $invoice->month,
                    ],
                    [
                        'amount' => $student->monthly_fee,
                        'status' => 'pending',
                        'due_date' => Carbon::create($invoice->year, $invoice->month, $student->due_day ?? Setting::getPaymentDueDay()),
                    ]
                );
            }
        }

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
            }
        }
        
        // Add material fee if pending
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
        }
        
        // Add extra hours
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
        }
    }
    
    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['student.guardian', 'items']);
        
        return view('invoices.show', compact('invoice'));
    }
    
    public function downloadPdf(Invoice $invoice)
    {
        ini_set('memory_limit', '256M');
        set_time_limit(300);

        try {
            $invoice->load(['student.guardian', 'items']);
            
            $settings = array_merge(
                Setting::getByGroup('company'),
                Setting::getByGroup('financial'),
                Setting::getByGroup('invoice')
            );
            
            /*
            // DEBUG: Return HTML directly to identify if error is in View or PDF generation
            return view('invoices.pdf', [
                'invoice' => $invoice,
                'settings' => $settings,
            ]);
            */
            
            $pdf = Pdf::loadView('invoices.pdf', [
                'invoice' => $invoice,
                'settings' => $settings,
            ]);
            
            $filename = "fatura_" . str_replace('/', '_', $invoice->invoice_number) . ".pdf";
            
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response()->make("
                <h1>Erro ao gerar PDF</h1>
                <p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>
                <p><strong>Arquivo:</strong> " . $e->getFile() . "</p>
                <p><strong>Linha:</strong> " . $e->getLine() . "</p>
                <pre>" . $e->getTraceAsString() . "</pre>
            ", 500);
        }
    }
    
    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice)
    {
        $invoice->update(['status' => 'sent']);
        
        return back()->with('success', 'Fatura marcada como enviada!');
    }
    
    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::today(),
        ]);
        
        return back()->with('success', 'Fatura marcada como paga!');
    }
    
    /**
     * Cancel invoice.
     */
    public function cancel(Invoice $invoice)
    {
        $invoice->update(['status' => 'cancelled']);
        
        return back()->with('success', 'Fatura cancelada!');
    }
    
    /**
     * Bulk generate invoices.
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        $year = $request->year;
        $month = $request->month;
        
        $students = Student::active()->get();
        $generated = 0;
        $skipped = 0;
        
        foreach ($students as $student) {
            // Check if invoice already exists
            $exists = Invoice::where('student_id', $student->id)
                ->forMonth($year, $month)
                ->exists();
                
            if ($exists) {
                $skipped++;
                continue;
            }
            
            // Check if student has any pending items
            $hasItems = MonthlyFee::where('student_id', $student->id)
                    ->forMonth($year, $month)
                    ->pending()
                    ->exists()
                || MaterialFee::where('student_id', $student->id)
                    ->forYear($year)
                    ->pending()
                    ->exists()
                || AttendanceLog::where('student_id', $student->id)
                    ->forMonth($year, $month)
                    ->where('extra_charge', '>', 0)
                    ->exists()
                || ($student->monthly_fee > 0); // Include if they have a base fee set
                    
            if (!$hasItems) {
                $skipped++;
                continue;
            }
            
            // Generate invoice using the same logic as single generate
            $this->generateForStudent($student, $year, $month);
            $generated++;
        }
        
        return back()->with('success', "Faturas geradas: {$generated}, Puladas: {$skipped}");
    }
    
    private function generateForStudent(Student $student, int $year, int $month): Invoice
    {
        $invoice = Invoice::create([
            'student_id' => $student->id,
            'year' => $year,
            'month' => $month,
            'status' => 'draft',
            'due_date' => Carbon::create($year, $month, $student->due_day ?? Setting::getPaymentDueDay()),
        ]);
        
        // Add monthly fees (ensure they exist first if student has a fee set)
        if ($student->monthly_fee > 0) {
            foreach ($student->activeEnrollments as $enrollment) {
                MonthlyFee::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'class_id' => $enrollment->class_id,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'amount' => $student->monthly_fee,
                        'status' => 'pending',
                        'due_date' => Carbon::create($year, $month, $student->due_day ?? Setting::getPaymentDueDay()),
                    ]
                );
            }
        }

        $monthlyFees = MonthlyFee::where('student_id', $student->id)
            ->forMonth($year, $month)
            ->pending()
            ->get();
            
        foreach ($monthlyFees as $fee) {
            $invoice->addItem('monthly_fee', "Mensalidade {$fee->reference}" . ($fee->classModel ? " - {$fee->classModel->name}" : ''), 1, $fee->remaining_amount);
        }
        
        // Add material fee
        $materialFee = MaterialFee::where('student_id', $student->id)
            ->forYear($year)
            ->pending()
            ->first();
            
        if ($materialFee) {
            $invoice->addItem('material_fee', "Taxa de Material {$year}", 1, $materialFee->remaining_amount);
        }
        
        // Add extra hours
        $extraHours = AttendanceLog::where('student_id', $student->id)
            ->forMonth($year, $month)
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

            $invoice->addItem('extra_hours', $description, 1, $totalExtraCharge);
        }
        
        return $invoice;
    }
}
