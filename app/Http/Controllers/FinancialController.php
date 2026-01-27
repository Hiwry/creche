<?php

namespace App\Http\Controllers;

use App\Models\MonthlyFee;
use App\Models\MaterialFee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FinancialController extends Controller
{
    /**
     * Display financial overview.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        
        // Monthly fees for selected month
        $monthlyFeesQuery = MonthlyFee::with(['student', 'classModel'])
            ->forMonth($year, $month);
            
        if ($request->filled('status')) {
            $monthlyFeesQuery->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $monthlyFeesQuery->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }
        
        $monthlyFees = $monthlyFeesQuery->orderBy('status')->paginate(20);
        
        // Summary stats
        $summary = [
            'total' => MonthlyFee::forMonth($year, $month)->sum('amount'),
            'paid' => MonthlyFee::forMonth($year, $month)->where('status', 'paid')->sum('amount_paid'),
            'pending' => MonthlyFee::forMonth($year, $month)->pending()->sum('amount'),
            'paid_count' => MonthlyFee::forMonth($year, $month)->where('status', 'paid')->count(),
            'pending_count' => MonthlyFee::forMonth($year, $month)->pending()->count(),
        ];
        
        return view('financial.index', compact('monthlyFees', 'summary', 'year', 'month'));
    }
    
    /**
     * Material fees page.
     */
    public function materialFees(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        
        $query = MaterialFee::with('student')->forYear($year);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $materialFees = $query->paginate(20);
        
        $summary = [
            'total' => MaterialFee::forYear($year)->sum('amount'),
            'paid' => MaterialFee::forYear($year)->where('status', 'paid')->sum('amount_paid'),
            'pending' => MaterialFee::forYear($year)->pending()->sum('amount'),
        ];
        
        return view('financial.material-fees', compact('materialFees', 'summary', 'year'));
    }
    
    /**
     * Show payment form.
     */
    public function showPaymentForm(Request $request)
    {
        $type = $request->input('type', 'monthly_fee');
        $id = $request->input('id');
        
        if ($type === 'monthly_fee' && $id) {
            $payable = MonthlyFee::with('student')->findOrFail($id);
        } elseif ($type === 'material_fee' && $id) {
            $payable = MaterialFee::with('student')->findOrFail($id);
        } else {
            abort(404);
        }
        
        return view('financial.payment-form', compact('payable', 'type'));
    }
    
    /**
     * Store payment.
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'payable_type' => 'required|in:monthly_fee,material_fee',
            'payable_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,pix,credit_card,debit_card,bank_transfer,other',
            'payment_date' => 'required|date',
            'receipt' => 'nullable|file|max:5120', // 5MB
        ]);
        
        // Determine the payable model
        if ($request->payable_type === 'monthly_fee') {
            $payable = MonthlyFee::findOrFail($request->payable_id);
            $payableType = MonthlyFee::class;
        } else {
            $payable = MaterialFee::findOrFail($request->payable_id);
            $payableType = MaterialFee::class;
        }
        
        // Handle receipt upload
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }
        
        // Create payment
        Payment::create([
            'student_id' => $payable->student_id,
            'payable_type' => $payableType,
            'payable_id' => $payable->id,
            'amount' => $request->amount,
            'method' => $request->method,
            'payment_date' => $request->payment_date,
            'receipt_path' => $receiptPath,
            'notes' => $request->notes,
            'received_by' => auth()->id(),
        ]);
        
        return redirect()->route('financial.index')
            ->with('success', 'Pagamento registrado com sucesso!');
    }
    
    /**
     * Quick mark as paid.
     */
    public function markAsPaid(Request $request, $type, $id)
    {
        if ($type === 'monthly_fee') {
            $payable = MonthlyFee::findOrFail($id);
            $payableType = MonthlyFee::class;
        } else {
            $payable = MaterialFee::findOrFail($id);
            $payableType = MaterialFee::class;
        }
        
        $remainingAmount = $payable->net_amount - $payable->amount_paid;
        
        if ($remainingAmount > 0) {
            Payment::create([
                'student_id' => $payable->student_id,
                'payable_type' => $payableType,
                'payable_id' => $payable->id,
                'amount' => $remainingAmount,
                'method' => $request->input('method', 'pix'),
                'payment_date' => Carbon::today(),
                'received_by' => auth()->id(),
            ]);
        }
        
        return back()->with('success', 'Pagamento registrado!');
    }
    
    /**
     * Generate monthly fees for all active students.
     */
    public function generateMonthlyFees(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        $year = $request->year;
        $month = $request->month;
        $dueDay = Setting::getPaymentDueDay();
        $defaultFee = Setting::getDefaultMonthlyFee();
        
        // Get all active students with enrollments
        $students = Student::active()
            ->whereHas('activeEnrollments')
            ->with('activeEnrollments.classModel')
            ->get();
        
        $created = 0;
        $skipped = 0;
        
        foreach ($students as $student) {
            foreach ($student->activeEnrollments as $enrollment) {
                // Check if fee already exists
                $exists = MonthlyFee::where('student_id', $student->id)
                    ->where('class_id', $enrollment->class_id)
                    ->forMonth($year, $month)
                    ->exists();
                    
                if ($exists) {
                    $skipped++;
                    continue;
                }
                
                $amount = $student->monthly_fee ?? ($enrollment->classModel->monthly_fee ?? $defaultFee);
                $studentDueDay = $student->due_day ?? $dueDay;
                
                MonthlyFee::create([
                    'student_id' => $student->id,
                    'class_id' => $enrollment->class_id,
                    'year' => $year,
                    'month' => $month,
                    'amount' => $amount,
                    'status' => 'pending',
                    'due_date' => Carbon::create($year, $month, $studentDueDay),
                ]);
                
                $created++;
            }
        }
        
        return back()->with('success', "Mensalidades geradas: {$created} novas, {$skipped} já existentes.");
    }
    
    /**
     * Payments history.
     */
    public function payments(Request $request)
    {
        $query = Payment::with(['student', 'receivedBy']);
        
        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }
        
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        
        if ($request->filled('search')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }
        
        $payments = $query->latest('payment_date')->paginate(20);
        
        // Summary
        $totalAmount = $query->sum('amount');
        
        return view('financial.payments', compact('payments', 'totalAmount'));
    }
}
