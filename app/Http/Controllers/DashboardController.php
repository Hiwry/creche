<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\MaterialFee;
use App\Models\AttendanceLog;
use App\Models\ClassModel;
use App\Models\Payment;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // Active students count
        $activeStudents = Student::active()->count();
        
        // Monthly fees stats for current month
        $monthlyFeesQuery = MonthlyFee::forMonth($currentYear, $currentMonth);
        $paidFees = (clone $monthlyFeesQuery)->where('status', 'paid')->count();
        $totalFees = $monthlyFeesQuery->count();
        $pendingFees = Student::active()
            ->withPendingFees()
            ->count();
        
        // Monthly revenue (paid this month)
        $monthlyRevenue = Payment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount');
        
        // Extra hours this month
        $extraHoursData = AttendanceLog::forMonth($currentYear, $currentMonth)
            ->selectRaw('SUM(extra_minutes) as total_minutes, SUM(extra_charge) as total_charge')
            ->first();
        
        // Payment status for chart (last 6 months)
        $paymentStatusData = $this->getPaymentStatusData();
        
        // Extra hours chart data (last 7 days)
        $extraHoursChartData = $this->getExtraHoursChartData();
        
        // Today's schedule
        $todaySchedule = ClassModel::active()
            ->whereJsonContains('days_of_week', strtolower(Carbon::now()->format('l')))
            ->orderBy('start_time')
            ->limit(5)
            ->get();
        
        // Recent activities/notices
        $recentPayments = Payment::with('student')
            ->latest()
            ->limit(5)
            ->get();
        
        // Expenses data
        $monthlyExpenses = Expense::getMonthlyTotal($currentYear, $currentMonth);
        $expensesByCategory = Expense::getByCategory($currentYear, $currentMonth);
        $recentExpenses = Expense::orderBy('created_at', 'desc')->limit(5)->get();
        
        return view('dashboard', compact(
            'activeStudents',
            'paidFees',
            'totalFees',
            'pendingFees',
            'monthlyRevenue',
            'extraHoursData',
            'paymentStatusData',
            'extraHoursChartData',
            'todaySchedule',
            'recentPayments',
            'monthlyExpenses',
            'expensesByCategory',
            'recentExpenses'
        ));
    }
    
    private function getPaymentStatusData(): array
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        $fees = MonthlyFee::forMonth($currentYear, $currentMonth)->get();
        
        $paid = $fees->where('status', 'paid')->count();
        $pending = $fees->whereIn('status', ['pending', 'partial', 'overdue'])->count();
        
        return [
            'paid' => $paid,
            'pending' => $pending,
            'paid_percentage' => $fees->count() > 0 ? round(($paid / $fees->count()) * 100) : 0,
            'pending_percentage' => $fees->count() > 0 ? round(($pending / $fees->count()) * 100) : 0,
        ];
    }
    
    private function getExtraHoursChartData(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $minutes = AttendanceLog::forDate($date->toDateString())
                ->sum('extra_minutes');
            
            $data[] = [
                'day' => $date->format('D'),
                'date' => $date->format('d/m'),
                'hours' => round($minutes / 60, 1),
                'minutes' => $minutes,
            ];
        }
        
        return $data;
    }
}
