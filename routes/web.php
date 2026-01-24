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
use App\Http\Controllers\Auth\LoginController;

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
    
    // Guardians
    Route::resource('guardians', GuardianController::class);
    
    // Classes
    Route::resource('classes', ClassController::class);
    Route::post('/classes/{class}/enroll', [ClassController::class, 'enrollStudent'])->name('classes.enroll');
    Route::delete('/classes/{class}/enrollments/{enrollment}', [ClassController::class, 'removeStudent'])->name('classes.remove-student');
    
    // Financial
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/', [FinancialController::class, 'index'])->name('index');
        Route::get('/material-fees', [FinancialController::class, 'materialFees'])->name('material-fees');
        Route::get('/payments', [FinancialController::class, 'payments'])->name('payments');
        Route::get('/payment-form', [FinancialController::class, 'showPaymentForm'])->name('payment-form');
        Route::post('/payments', [FinancialController::class, 'storePayment'])->name('store-payment');
        Route::post('/mark-paid/{type}/{id}', [FinancialController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/generate-monthly-fees', [FinancialController::class, 'generateMonthlyFees'])->name('generate-monthly-fees');
    });
    
    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        Route::post('/quick', [AttendanceController::class, 'quickRegister'])->name('quick');
        Route::get('/extra-hours', [AttendanceController::class, 'extraHoursReport'])->name('extra-hours');
        Route::get('/{log}/edit', [AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{log}', [AttendanceController::class, 'update'])->name('update');
        Route::delete('/{log}', [AttendanceController::class, 'destroy'])->name('destroy');
    });
    
    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('/generate', [InvoiceController::class, 'generate'])->name('generate');
        Route::post('/bulk-generate', [InvoiceController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::post('/{invoice}/send', [InvoiceController::class, 'markAsSent'])->name('send');
        Route::post('/{invoice}/paid', [InvoiceController::class, 'markAsPaid'])->name('paid');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
        Route::post('/{invoice}/recalculate', [InvoiceController::class, 'recalculate'])->name('recalculate');
    });
    
    // Expenses
    Route::prefix('expenses')->name('expenses.')->group(function () {
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
    });

    // School Materials
    Route::prefix('school-materials')->name('school-materials.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SchoolMaterialController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SchoolMaterialController::class, 'store'])->name('store');
        Route::delete('/{schoolMaterial}', [\App\Http\Controllers\SchoolMaterialController::class, 'destroy'])->name('destroy');
        Route::get('/bulk-check', [\App\Http\Controllers\SchoolMaterialController::class, 'bulkCheck'])->name('bulk-check');
        Route::post('/bulk-check', [\App\Http\Controllers\SchoolMaterialController::class, 'updateBulkCheck'])->name('update-bulk-check');
        Route::post('/{student}/update-checklist', [\App\Http\Controllers\SchoolMaterialController::class, 'updateStudentChecklist'])->name('student-checklist.update');
    });
});
