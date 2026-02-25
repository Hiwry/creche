<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttendanceLog;
use App\Models\MaterialFee;
use App\Models\MonthlyFee;
use App\Models\Invoice;
use App\Models\SchoolMaterialUsage;
use App\Models\Setting;
use App\Support\BillingCycle;

class RepairJanuaryInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:repair-january
                            {year=2026 : Ano de referência (YYYY)}
                            {month=1 : Mês de referência (1-12)}
                            {--force : Forçar o reparo mesmo em mensalidades que constam como pagas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repara faturas zeradas de um mês/ano, recompondo mensalidades e itens pendentes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $month = (int) $this->argument('month');
        $force = $this->option('force');

        if ($month < 1 || $month > 12) {
            $this->error('O mês deve estar entre 1 e 12.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Iniciando reparo de faturas para %02d/%d...',
            $month,
            $year
        ));

        $invoices = Invoice::with([
            'student.activeEnrollments.classModel',
            'student.activeSportEnrollments.sport',
        ])
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        $this->info("Encontradas " . $invoices->count() . " faturas.");

        $fixedCount = 0;
        $createdFees = 0;
        $updatedFees = 0;
        $reopenedFees = 0;
        $deletedInvoices = 0;

        foreach ($invoices as $invoice) {
            $student = $invoice->student;
            if (!$student) {
                continue;
            }

            $this->info("Processando Aluno: {$student->name} (ID: {$student->id})");

            $dueDate = $this->makeDueDate(
                $year,
                $month,
                (int) ($student->due_day ?? Setting::getPaymentDueDay())
            );

            // 1) Garantir mensalidades válidas para o mês/ano.
            if ($student->activeEnrollments->isEmpty()) {
                $resolvedAmount = $this->resolveMonthlyFeeAmount($student);

                if ($resolvedAmount > 0) {
                    $fee = MonthlyFee::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'class_id' => null,
                            'year' => $year,
                            'month' => $month,
                        ],
                        [
                            'amount' => $resolvedAmount,
                            'status' => 'pending',
                            'due_date' => $dueDate,
                        ]
                    );

                    if ($fee->wasRecentlyCreated) {
                        $createdFees++;
                    } elseif ((float) $fee->amount <= 0) {
                        $fee->update([
                            'amount' => $resolvedAmount,
                            'due_date' => $dueDate,
                        ]);
                        $updatedFees++;
                    }
                }
            } else {
                foreach ($student->activeEnrollments as $enrollment) {
                    $resolvedAmount = $this->resolveMonthlyFeeAmount($student, $enrollment->classModel);

                    if ($resolvedAmount <= 0) {
                        continue;
                    }

                    $fee = MonthlyFee::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'class_id' => $enrollment->class_id,
                            'year' => $year,
                            'month' => $month,
                        ],
                        [
                            'amount' => $resolvedAmount,
                            'status' => 'pending',
                            'due_date' => $dueDate,
                        ]
                    );

                    if ($fee->wasRecentlyCreated) {
                        $createdFees++;
                    } elseif ((float) $fee->amount <= 0) {
                        $fee->update([
                            'amount' => $resolvedAmount,
                            'due_date' => $dueDate,
                        ]);
                        $updatedFees++;
                    }
                }
            }

            $monthlyFeesData = MonthlyFee::with('classModel')
                ->where('student_id', $student->id)
                ->forMonth($year, $month)
                ->get();

            // 2) Reabrir mensalidades inconsistentes e corrigir valores zerados.
            foreach ($monthlyFeesData as $fee) {
                $isGhostPaid = $fee->status === 'paid' && !$fee->payments()->exists();
                $forceReset = $force && (float) $invoice->total <= 0 && $fee->status === 'paid';

                if ($isGhostPaid || $forceReset) {
                    $fee->update([
                        'status' => 'pending',
                        'amount_paid' => 0,
                    ]);
                    $fee->refresh();
                    $reopenedFees++;
                }

                $resolvedAmount = $this->resolveMonthlyFeeAmount($student, $fee->classModel);
                if ((float) $fee->amount <= 0 && $resolvedAmount > 0) {
                    $fee->update([
                        'amount' => $resolvedAmount,
                        'due_date' => $dueDate,
                    ]);
                    $fee->refresh();
                    $updatedFees++;
                }
            }

            // 3) Limpar e reconstruir itens.
            $invoice->items()->delete();

            foreach ($monthlyFeesData as $fee) {
                $remainingAmount = (float) $fee->remaining_amount;
                if ($remainingAmount > 0) {
                    $invoice->addItem(
                        'monthly_fee',
                        "Mensalidade {$invoice->reference}" . ($fee->classModel ? " - {$fee->classModel->name}" : ''),
                        1,
                        $remainingAmount
                    );
                }
            }

            $materialFee = MaterialFee::where('student_id', $student->id)
                ->forYear($year)
                ->pending()
                ->first();

            if ($materialFee && $materialFee->remaining_amount > 0) {
                $invoice->addItem('material_fee', "Taxa de Material {$year}", 1, $materialFee->remaining_amount);
            }

            $extraHours = AttendanceLog::where('student_id', $student->id)
                ->forMonth($year, $month)
                ->where('extra_charge', '>', 0)
                ->get();

            $extraCharge = (float) $extraHours->sum('extra_charge');
            if ($extraCharge > 0) {
                $invoice->addItem('extra_hours', "Horas extras", 1, $extraCharge);
            }

            $materialUsages = SchoolMaterialUsage::where('student_id', $student->id)
                ->where(function ($query) use ($invoice) {
                    $query->whereNull('invoice_id')
                        ->orWhere('invoice_id', $invoice->id);
                })
                ->whereYear('usage_date', $year)
                ->whereMonth('usage_date', $month)
                ->with('material')
                ->get();

            foreach ($materialUsages as $usage) {
                $quantity = (float) $usage->quantity;
                $unitPrice = (float) $usage->value;

                if ($quantity <= 0 || $unitPrice <= 0) {
                    continue;
                }

                $invoice->addItem(
                    'material_fee',
                    "Material: " . ($usage->material->name ?? 'Material') . " (" . $usage->usage_date->format('d/m/Y') . ")",
                    $quantity,
                    $unitPrice,
                    $usage->notes
                );

                if (!$usage->invoice_id) {
                    $usage->update(['invoice_id' => $invoice->id]);
                }
            }

            foreach ($student->activeSportEnrollments as $enrollment) {
                $sportFee = (float) $enrollment->monthly_fee;

                if ($sportFee <= 0) {
                    continue;
                }

                $invoice->addItem(
                    'sport_fee',
                    "Esporte: " . ($enrollment->sport->name ?? 'Esporte'),
                    1,
                    $sportFee
                );
            }

            $invoice->recalculateTotals();
            $invoice->refresh();

            if (!$invoice->items()->exists() || (float) $invoice->total <= 0) {
                $invoice->items()->delete();
                $invoice->delete();
                $deletedInvoices++;
                $this->warn(" - Fatura removida (sem itens cobraveis apos recalculo).");
                continue;
            }

            $this->info(" - Fatura recalculada. Novo total: R$ {$invoice->total}");
            $fixedCount++;
        }

        $this->info("\nReparo concluído!");
        $this->info("Faturas corrigidas: {$fixedCount}");
        $this->info("Faturas removidas (sem cobrança): {$deletedInvoices}");
        $this->info("Mensalidades criadas: {$createdFees}");
        $this->info("Mensalidades atualizadas: {$updatedFees}");
        $this->info("Mensalidades reabertas: {$reopenedFees}");

        return self::SUCCESS;
    }

    private function makeDueDate(int $year, int $month, int $day)
    {
        return BillingCycle::makeDueDate((int) $year, (int) $month, (int) $day);
    }

    private function resolveMonthlyFeeAmount(\App\Models\Student $student, ?\App\Models\ClassModel $classModel = null): float
    {
        $studentFee = (float) $student->monthly_fee;
        if ($studentFee > 0) {
            return $studentFee;
        }

        if ($classModel && (float) $classModel->monthly_fee > 0) {
            return (float) $classModel->monthly_fee;
        }

        return 0.0;
    }
}
