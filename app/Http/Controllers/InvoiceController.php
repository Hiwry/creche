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
            'due_date' => Carbon::create($year, $month, Setting::getPaymentDueDay()),
        ]);
        
        // Add monthly fees
        $monthlyFees = MonthlyFee::where('student_id', $student->id)
            ->forMonth($year, $month)
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
        $materialFee = MaterialFee::where('student_id', $student->id)
            ->forYear($year)
            ->pending()
            ->first();
            
        if ($materialFee && $materialFee->remaining_amount > 0) {
            $invoice->addItem(
                'material_fee',
                "Taxa de Material {$year}",
                1,
                $materialFee->remaining_amount
            );
        }
        
        // Add extra hours
        $extraHours = AttendanceLog::where('student_id', $student->id)
            ->forMonth($year, $month)
            ->where('extra_minutes', '>', 0)
            ->get();
            
        $totalExtraMinutes = $extraHours->sum('extra_minutes');
        $totalExtraCharge = $extraHours->sum('extra_charge');
        
        if ($totalExtraCharge > 0) {
            $hours = floor($totalExtraMinutes / 60);
            $minutes = $totalExtraMinutes % 60;
            $description = "Horas extras ({$hours}h{$minutes}min)";
            
            $invoice->addItem(
                'extra_hours',
                $description,
                1,
                $totalExtraCharge
            );
        }
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Fatura gerada com sucesso!');
    }
    
    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['student.guardian', 'items']);
        
        return view('invoices.show', compact('invoice'));
    }
    
    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['student.guardian', 'items']);
        
        $settings = Setting::getByGroup('company');
        
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ]);
        
        $filename = "fatura_" . str_replace('/', '_', $invoice->invoice_number) . ".pdf";
        
        return $pdf->download($filename);
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
                    ->exists();
                    
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
            'due_date' => Carbon::create($year, $month, Setting::getPaymentDueDay()),
        ]);
        
        // Add monthly fees
        $monthlyFees = MonthlyFee::where('student_id', $student->id)
            ->forMonth($year, $month)
            ->pending()
            ->get();
            
        foreach ($monthlyFees as $fee) {
            $invoice->addItem('monthly_fee', "Mensalidade {$fee->reference}", 1, $fee->remaining_amount);
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
        $totalExtraCharge = AttendanceLog::where('student_id', $student->id)
            ->forMonth($year, $month)
            ->sum('extra_charge');
            
        if ($totalExtraCharge > 0) {
            $invoice->addItem('extra_hours', "Horas extras do mês", 1, $totalExtraCharge);
        }
        
        return $invoice;
    }
}
