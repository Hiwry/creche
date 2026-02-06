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
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $params = [];

        if ($request->filled('year')) {
            $params['year'] = $request->year;
        }

        if ($request->filled('month')) {
            $params['month'] = $request->month;
        }

        if ($request->filled('status')) {
            $params['status'] = $request->status;
        }

        if ($request->filled('search')) {
            $params['search'] = $request->search;
        }

        return redirect()->route('financial.index', $params);
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
            'due_date' => $this->makeDueDate($year, $month, (int) ($student->due_day ?? Setting::getPaymentDueDay())),
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
                        'due_date' => $this->makeDueDate($invoice->year, $invoice->month, (int) ($student->due_day ?? Setting::getPaymentDueDay())),
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
        if (!auth()->user()?->canViewInvoiceValues()) {
            return redirect()
                ->route('financial.index')
                ->with('error', 'Você não tem permissão para visualizar valores da fatura.');
        }

        $invoice->load(['student.guardian', 'items']);
        
        return view('invoices.show', compact('invoice'));
    }
    
    public function downloadPdf(Invoice $invoice)
    {
        if (!auth()->user()?->canDownloadInvoicePdf()) {
            return redirect()
                ->route('financial.index')
                ->with('error', 'Você não tem permissão para baixar a fatura.');
        }
        ini_set('memory_limit', '256M');
        set_time_limit(300);

        try {
            [$pdf, $filename] = $this->buildInvoicePdf($invoice);

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
     * Open receipt PDF in browser for print.
     */
    public function printPdf(Invoice $invoice)
    {
        if (!auth()->user()?->canViewInvoiceValues()) {
            return redirect()
                ->route('financial.index')
                ->with('error', 'Você não tem permissão para visualizar valores da fatura.');
        }

        ini_set('memory_limit', '256M');
        set_time_limit(300);

        if ($invoice->status !== 'paid') {
            return back()->with('error', 'O recibo só pode ser emitido para faturas pagas.');
        }

        try {
            [$pdf, $filename] = $this->buildReceiptPdf($invoice);
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao abrir recibo para impressão: ' . $e->getMessage());
        }
    }

    /**
     * Send receipt by e-mail with attached PDF.
     */
    public function sendReceipt(Invoice $invoice)
    {
        if (!auth()->user()?->canSendInvoices()) {
            return back()->with('error', 'Você não tem permissão para enviar recibos.');
        }

        $invoice->load(['student.guardian', 'items']);

        if ($invoice->status !== 'paid') {
            return back()->with('error', 'O recibo só pode ser enviado para faturas pagas.');
        }

        $recipientEmail = $invoice->student?->guardian?->email;
        $recipientName = $invoice->student?->guardian?->name ?? $invoice->student?->name;

        if (!$recipientEmail) {
            return back()->with('error', 'O responsável deste aluno não possui e-mail cadastrado.');
        }

        try {
            [$pdf, $filename, $settings] = $this->buildReceiptPdf($invoice);
            $companyName = $settings['company_name'] ?? config('app.name');
            $pdfContent = $pdf->output();

            Mail::send('emails.invoice-receipt', [
                'invoice' => $invoice,
                'companyName' => $companyName,
            ], function ($message) use ($recipientEmail, $recipientName, $filename, $pdfContent, $invoice, $companyName) {
                $message->to($recipientEmail, $recipientName)
                    ->subject("Recibo de pagamento {$invoice->invoice_number} - {$companyName}")
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });

            return back()->with('success', "Recibo enviado para {$recipientEmail}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao enviar recibo: ' . $e->getMessage());
        }
    }
    
    /**
     * Send invoice by e-mail with attached PDF.
     */
    public function sendInvoicePdf(Invoice $invoice)
    {
        if (!auth()->user()?->canSendInvoices()) {
            return back()->with('error', 'Você não tem permissão para enviar faturas.');
        }

        $invoice->load(['student.guardian', 'items']);

        $recipientEmail = $invoice->student?->guardian?->email;
        $recipientName = $invoice->student?->guardian?->name ?? $invoice->student?->name;

        if (!$recipientEmail) {
            return back()->with('error', 'O responsável deste aluno não possui e-mail cadastrado.');
        }

        try {
            [$pdf, $filename, $settings] = $this->buildInvoicePdf($invoice);
            $companyName = $settings['company_name'] ?? config('app.name');
            $pdfContent = $pdf->output();

            Mail::send('emails.invoice-pdf', [
                'invoice' => $invoice,
                'companyName' => $companyName,
            ], function ($message) use ($recipientEmail, $recipientName, $filename, $pdfContent, $invoice, $companyName) {
                $message->to($recipientEmail, $recipientName)
                    ->subject("Fatura {$invoice->invoice_number} - {$companyName}")
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });

            if ($invoice->status === 'draft') {
                $invoice->update(['status' => 'sent']);
            }

            return back()->with('success', "Fatura enviada para {$recipientEmail}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao enviar fatura: ' . $e->getMessage());
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
        if (!auth()->user()?->canMarkInvoicePaid()) {
            return back()->with('error', 'Você não tem permissão para marcar faturas como pagas.');
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::today(),
        ]);
        
        return back()->with('success', 'Fatura marcada como paga!');
    }

    /**
     * Remove paid status from invoice.
     */
    public function markAsUnpaid(Invoice $invoice)
    {
        if ($invoice->status !== 'paid') {
            return back()->with('error', 'A fatura não está marcada como paga.');
        }

        $invoice->update([
            'status' => 'sent',
            'paid_at' => null,
        ]);

        return back()->with('success', 'Pagamento removido da fatura.');
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
            'due_date' => $this->makeDueDate($year, $month, (int) ($student->due_day ?? Setting::getPaymentDueDay())),
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
                        'due_date' => $this->makeDueDate($year, $month, (int) ($student->due_day ?? Setting::getPaymentDueDay())),
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

    private function makeDueDate(int $year, int $month, int $day): Carbon
    {
        $base = Carbon::create($year, $month, 1);
        $day = max(1, min($day, $base->daysInMonth));

        return Carbon::create($year, $month, $day);
    }

    private function buildInvoicePdf(Invoice $invoice): array
    {
        $invoice->load(['student.guardian', 'items']);

        $settings = $this->getPdfSettings();

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
        ]);

        $filename = "fatura_" . str_replace('/', '_', $invoice->invoice_number) . ".pdf";

        return [$pdf, $filename, $settings];
    }

    private function buildReceiptPdf(Invoice $invoice): array
    {
        $invoice->load(['student.guardian', 'items']);

        $settings = $this->getPdfSettings();
        $issuedAt = $invoice->paid_at ? Carbon::parse($invoice->paid_at) : Carbon::today();

        $pdf = Pdf::loadView('invoices.receipt-pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
            'issuedAt' => $issuedAt,
        ]);

        $filename = "recibo_" . str_replace('/', '_', $invoice->invoice_number) . ".pdf";

        return [$pdf, $filename, $settings];
    }

    private function getPdfSettings(): array
    {
        return array_merge(
            Setting::getByGroup('company'),
            Setting::getByGroup('financial'),
            Setting::getByGroup('invoice')
        );
    }
}
