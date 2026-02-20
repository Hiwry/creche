<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\IncomeTaxController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SportController;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Students
    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/documents', [StudentController::class, 'uploadDocument'])->name('students.documents.upload');
    Route::delete('/students/{student}/documents/{document}', [StudentController::class, 'deleteDocument'])->name('students.documents.delete');
    Route::get('/students/{student}/income-tax', [IncomeTaxController::class, 'show'])->name('students.income-tax');
    Route::get('/students/{student}/income-tax/pdf', [IncomeTaxController::class, 'downloadPdf'])->name('students.income-tax.pdf');
    
    // Guardians
    Route::resource('guardians', GuardianController::class);
    
    // Classes
    Route::resource('classes', ClassController::class);
    Route::post('/classes/{class}/enroll', [ClassController::class, 'enrollStudent'])->name('classes.enroll');
    Route::delete('/classes/{class}/enrollments/{enrollment}', [ClassController::class, 'removeStudent'])->name('classes.remove-student');
    
    // Financial
    Route::prefix('financial')->name('financial.')->middleware('financial.access')->group(function () {
        Route::get('/', [FinancialController::class, 'index'])->name('index');
        Route::get('/material-fees', [FinancialController::class, 'materialFees'])->name('material-fees');
        Route::get('/payments', [FinancialController::class, 'payments'])->name('payments');
        Route::get('/payment-form', [FinancialController::class, 'showPaymentForm'])->name('payment-form');
        Route::post('/payments', [FinancialController::class, 'storePayment'])->name('store-payment');
        Route::post('/mark-paid/{type}/{id}', [FinancialController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/unmark-paid/{type}/{id}', [FinancialController::class, 'markAsUnpaid'])->name('unmark-paid');
        Route::post('/generate-monthly-fees', [FinancialController::class, 'generateMonthlyFees'])->name('generate-monthly-fees');
        Route::post('/reconcile-fees/{student}', [FinancialController::class, 'reconcileFees'])->name('reconcile-fees');
        Route::post('/bulk-reconcile-fees', [FinancialController::class, 'bulkReconcileFees'])->name('reconcile-all');
    });
    
    Route::get('/debug-january', function() {
        $year = 2026;
        $month = 1;
        $students = \App\Models\Student::active()->get();
        $output = "<h1>Diagnóstico Janeiro 2026</h1><table border='1'><tr><th>ID</th><th>Nome</th><th>Fee Perfil</th><th>Fatura Total</th><th>MF Amt</th><th>MF Paid</th><th>MF Rem</th><th>MF Status</th><th>Payments</th><th>Items #</th></tr>";
        foreach ($students as $s) {
            $inv = \App\Models\Invoice::where('student_id', $s->id)->where('year', $year)->where('month', $month)->first();
            $mf = \App\Models\MonthlyFee::where('student_id', $s->id)->where('year', $year)->where('month', $month)->first();
            
            $total = $inv ? $inv->total : 'N/A';
            $items_count = $inv ? $inv->items()->count() : 'N/A';
            
            $mf_amt = $mf ? $mf->amount : 'N/A';
            $mf_paid = $mf ? $mf->amount_paid : 'N/A';
            $mf_rem = $mf ? $mf->remaining_amount : 'N/A';
            $mf_status = $mf ? $mf->status : 'N/A';
            
            $pmt_details = "";
            if ($mf) {
                foreach ($mf->payments as $p) {
                    $pmt_details .= "ID:{$p->id}, R\$:{$p->amount}, {$p->payment_date}<br>";
                }
            }
            if (empty($pmt_details)) $pmt_details = "0";
            
            $output .= "<tr><td>{$s->id}</td><td>{$s->name}</td><td>{$s->monthly_fee}</td><td>{$total}</td><td>{$mf_amt}</td><td>{$mf_paid}</td><td>{$mf_rem}</td><td>{$mf_status}</td><td>{$pmt_details}</td><td>{$items_count}</td></tr>";
        }
        $output .= "</table>";
        return $output;
    })->name('debug-january');

    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/report', [AttendanceController::class, 'report'])->name('report');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        Route::post('/quick', [AttendanceController::class, 'quickRegister'])->name('quick');
        Route::get('/extra-hours', [AttendanceController::class, 'extraHoursReport'])->name('extra-hours');
        Route::post('/extra-hours', [AttendanceController::class, 'storeExtraHours'])->name('extra-hours.store');
        Route::get('/{log}/edit', [AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{log}', [AttendanceController::class, 'update'])->name('update');
        Route::delete('/{log}', [AttendanceController::class, 'destroy'])->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/birthdays', [BirthdayController::class, 'index'])->name('birthdays');
    });
    
    // Invoices
    Route::prefix('invoices')->name('invoices.')->middleware('financial.access')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('/generate', [InvoiceController::class, 'generate'])->name('generate');
        Route::post('/bulk-generate', [InvoiceController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::get('/{invoice}/print', [InvoiceController::class, 'printPdf'])->name('print');
        Route::post('/{invoice}/send-pdf', [InvoiceController::class, 'sendInvoicePdf'])->name('send-pdf');
        Route::post('/{invoice}/send-receipt', [InvoiceController::class, 'sendReceipt'])->name('send-receipt');
        Route::post('/{invoice}/send', [InvoiceController::class, 'markAsSent'])->name('send');
        Route::post('/{invoice}/paid', [InvoiceController::class, 'markAsPaid'])->name('paid');
        Route::post('/{invoice}/unpaid', [InvoiceController::class, 'markAsUnpaid'])->name('unpaid');
        Route::post('/{invoice}/apply-punctual-discount', [InvoiceController::class, 'applyPunctualDiscount'])->name('apply-punctual-discount');
        Route::post('/{invoice}/remove-discount', [InvoiceController::class, 'removeDiscount'])->name('remove-discount');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
        Route::post('/{invoice}/recalculate', [InvoiceController::class, 'recalculate'])->name('recalculate');
    });
    
    // Expenses
    Route::prefix('expenses')->name('expenses.')->middleware('financial.access')->group(function () {
        Route::get('/', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ExpenseController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('store');
        Route::post('/quick', [\App\Http\Controllers\ExpenseController::class, 'quickStore'])->name('quick');
        Route::get('/{expense}/edit', [\App\Http\Controllers\ExpenseController::class, 'edit'])->name('edit');
        Route::put('/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('destroy');
    });
    
    // Settings (admin only)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::post('/logo', [SettingController::class, 'uploadLogo'])->name('upload-logo');
        Route::post('/users', [SettingController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [SettingController::class, 'updateUser'])->name('users.update');
        Route::post('/password', [SettingController::class, 'updatePassword'])->name('password');
    });

    // School Materials
    Route::prefix('school-materials')->name('school-materials.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SchoolMaterialController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SchoolMaterialController::class, 'store'])->name('store');
        Route::delete('/{schoolMaterial}', [\App\Http\Controllers\SchoolMaterialController::class, 'destroy'])->name('destroy');
        Route::get('/bulk-check', [\App\Http\Controllers\SchoolMaterialController::class, 'bulkCheck'])->name('bulk-check');
        Route::post('/bulk-check', [\App\Http\Controllers\SchoolMaterialController::class, 'updateBulkCheck'])->name('update-bulk-check');
        Route::post('/{student}/update-checklist', [\App\Http\Controllers\SchoolMaterialController::class, 'updateStudentChecklist'])->name('student-checklist.update');
        Route::post('/record-usage', [\App\Http\Controllers\SchoolMaterialController::class, 'recordUsage'])->name('record-usage');
    });

    // Sports
    Route::resource('sports', SportController::class);
    Route::post('/sports/{sport}/enroll', [SportController::class, 'enroll'])->name('sports.enroll');
    Route::post('/sports/unenroll/{enrollment}', [SportController::class, 'unenroll'])->name('sports.unenroll');
});
