<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\MaterialFee;
use App\Models\AttendanceLog;
use App\Models\ClassModel;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        [$startDate, $endDate, $period, $periodLabel] = $this->resolvePeriod($request);
        
        // Active students count
        $activeStudents = Student::active()->count();
        
        // Monthly fees stats for selected period (by due_date)
        $monthlyFeesQuery = MonthlyFee::whereBetween('due_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ]);
        $paidFees = (clone $monthlyFeesQuery)->where('status', 'paid')->count();
        $totalFees = $monthlyFeesQuery->count();
        $pendingFees = MonthlyFee::whereBetween('due_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereHas('student', function ($q) {
                $q->where('status', 'active');
            })
            ->distinct('student_id')
            ->count('student_id');
        
        // Revenue for selected period
        $monthlyRevenue = Payment::whereBetween('payment_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->sum('amount');
        
        // Extra hours for selected period
        $extraHoursData = AttendanceLog::whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->selectRaw('SUM(extra_minutes) as total_minutes, SUM(extra_charge) as total_charge')
            ->first();
        
        // Payment status for chart (selected period)
        $paymentStatusData = $this->getPaymentStatusData($startDate, $endDate);
        
        // Extra hours chart data (selected period)
        $extraHoursChartData = $this->getExtraHoursChartData($startDate, $endDate);
        
        // Today's schedule
        $todaySchedule = ClassModel::active()
            ->whereJsonContains('days_of_week', strtolower(Carbon::now()->format('l')))
            ->orderBy('start_time')
            ->limit(5)
            ->get();
        
        // Recent activities/notices
        $recentPayments = Payment::with('student')
            ->whereBetween('payment_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->latest('payment_date')
            ->limit(5)
            ->get();
        
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
            'startDate',
            'endDate',
            'period',
            'periodLabel'
        ));
    }
    
    private function getPaymentStatusData(Carbon $startDate, Carbon $endDate): array
    {
        $fees = MonthlyFee::whereBetween('due_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get();
        
        $paid = $fees->where('status', 'paid')->count();
        $pending = $fees->whereIn('status', ['pending', 'partial', 'overdue'])->count();
        
        return [
            'paid' => $paid,
            'pending' => $pending,
            'paid_percentage' => $fees->count() > 0 ? round(($paid / $fees->count()) * 100) : 0,
            'pending_percentage' => $fees->count() > 0 ? round(($pending / $fees->count()) * 100) : 0,
        ];
    }
    
    private function getExtraHoursChartData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [];
        $days = $startDate->diffInDays($endDate) + 1;

        $daysMap = [
            'Sun' => 'Dom', 'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua',
            'Thu' => 'Qui', 'Fri' => 'Sex', 'Sat' => 'Sáb'
        ];

        if ($days <= 14) {
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $minutes = AttendanceLog::whereDate('date', $cursor->toDateString())
                    ->sum('extra_minutes');

                $data[] = [
                    'label' => $daysMap[$cursor->format('D')],
                    'hours' => round($minutes / 60, 1),
                    'minutes' => $minutes,
                ];

                $cursor->addDay();
            }
        } elseif ($days <= 90) {
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $weekStart = $cursor->copy();
                $weekEnd = $cursor->copy()->addDays(6);
                if ($weekEnd->gt($endDate)) {
                    $weekEnd = $endDate->copy();
                }

                $minutes = AttendanceLog::whereBetween('date', [
                        $weekStart->toDateString(),
                        $weekEnd->toDateString(),
                    ])
                    ->sum('extra_minutes');

                $data[] = [
                    'label' => $weekStart->format('d/m') . '-' . $weekEnd->format('d/m'),
                    'hours' => round($minutes / 60, 1),
                    'minutes' => $minutes,
                ];

                $cursor = $weekEnd->addDay();
            }
        } else {
            $cursor = $startDate->copy()->startOfMonth();
            $endMonth = $endDate->copy()->startOfMonth();
            while ($cursor->lte($endMonth)) {
                $monthStart = $cursor->copy();
                $monthEnd = $cursor->copy()->endOfMonth();
                if ($monthEnd->gt($endDate)) {
                    $monthEnd = $endDate->copy();
                }

                $minutes = AttendanceLog::whereBetween('date', [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ])
                    ->sum('extra_minutes');

                $data[] = [
                    'label' => \App\Models\MonthlyFee::MONTHS[$monthStart->month] ?? $monthStart->format('m/Y'),
                    'hours' => round($minutes / 60, 1),
                    'minutes' => $minutes,
                ];

                $cursor->addMonth();
            }
        }

        return $data;
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->input('period', 'this_month');

        if ($period === 'custom') {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
            } else {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }
        } else {
            switch ($period) {
                case 'today':
                    $startDate = Carbon::today()->startOfDay();
                    $endDate = Carbon::today()->endOfDay();
                    break;
                case 'last_7_days':
                    $startDate = Carbon::now()->subDays(6)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
                    $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth();
                    break;
                case 'this_month':
                default:
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    $period = 'this_month';
                    break;
            }
        }

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $periodLabel = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');

        return [$startDate, $endDate, $period, $periodLabel];
    }
}
