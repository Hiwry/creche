<?php

namespace App\Http\Controllers;

use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IncomeTaxController extends Controller
{
    public function show(Request $request, Student $student)
    {
        [$year, $yearStart, $yearEnd] = $this->resolveYear($request);

        $payments = $this->getPayments($student, $yearStart, $yearEnd);
        $items = $this->buildItems($payments);
        $totalPaid = $items->sum('amount');
        $settings = Setting::getByGroup('company');
        $valueInWords = $this->moneyToWordsPtBr($totalPaid);

        return view('reports.income-tax', [
            'student' => $student,
            'year' => $year,
            'items' => $items,
            'totalPaid' => $totalPaid,
            'settings' => $settings,
            'valueInWords' => $valueInWords,
        ]);
    }

    public function downloadPdf(Request $request, Student $student)
    {
        [$year, $yearStart, $yearEnd] = $this->resolveYear($request);

        $payments = $this->getPayments($student, $yearStart, $yearEnd);
        $items = $this->buildItems($payments);
        $totalPaid = $items->sum('amount');
        $settings = Setting::getByGroup('company');
        $valueInWords = $this->moneyToWordsPtBr($totalPaid);

        $pdf = Pdf::loadView('reports.income-tax-pdf', [
            'student' => $student,
            'year' => $year,
            'items' => $items,
            'totalPaid' => $totalPaid,
            'settings' => $settings,
            'valueInWords' => $valueInWords,
            'issueDate' => Carbon::today(),
        ]);

        $filename = "declaracao_ir_{$student->id}_{$year}.pdf";

        return $pdf->download($filename);
    }

    private function resolveYear(Request $request): array
    {
        $defaultYear = Carbon::now()->subYear()->year;
        $year = (int) $request->input('year', $defaultYear);

        $currentYear = Carbon::now()->year;
        if ($year < 2000 || $year > $currentYear + 1) {
            $year = $defaultYear;
        }

        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        return [$year, $yearStart, $yearEnd];
    }

    private function getPayments(Student $student, Carbon $start, Carbon $end)
    {
        return Payment::with('payable')
            ->where('student_id', $student->id)
            ->where('payable_type', MonthlyFee::class)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->where('amount', '>', 0)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
    }

    private function buildItems($payments)
    {
        return $payments->groupBy('payable_id')->map(function ($group) {
            $fee = $group->first()->payable;
            $latestPayment = $group->sortByDesc('payment_date')->first();

            return [
                'fee' => $fee,
                'payment_date' => $latestPayment?->payment_date,
                'amount' => $group->sum('amount'),
            ];
        })->sortBy(function ($item) {
            if ($item['fee']) {
                return ((int) $item['fee']->year * 100) + (int) $item['fee']->month;
            }

            return $item['payment_date'] ? $item['payment_date']->format('Ymd') : 0;
        })->values();
    }

    private function moneyToWordsPtBr(float $value): ?string
    {
        if (!class_exists('NumberFormatter')) {
            return null;
        }

        $formatter = new \NumberFormatter('pt_BR', \NumberFormatter::SPELLOUT);
        $integer = (int) floor($value);
        $cents = (int) round(($value - $integer) * 100);

        $words = $formatter->format($integer);
        if ($words === false) {
            return null;
        }

        $words = ucfirst($words) . ($integer === 1 ? ' real' : ' reais');

        if ($cents > 0) {
            $centsWords = $formatter->format($cents);
            if ($centsWords !== false) {
                $words .= ' e ' . $centsWords . ($cents === 1 ? ' centavo' : ' centavos');
            }
        }

        return $words;
    }
}
