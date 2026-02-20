<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\BillingCycle;

class RepairJanuaryInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:repair-january {--force : Forçar o reparo mesmo em mensalidades que constam como pagas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repara faturas de Janeiro/2026 que ficaram zeradas, resetando mensalidades se necessário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = 2026;
        $month = 1;
        $force = $this->option('force');

        $this->info("Iniciando reparo AGGRESSIVO de faturas para 01/2026...");

        $invoices = Invoice::where('year', $year)->where('month', $month)->get();
        $this->info("Encontradas " . $invoices->count() . " faturas.");

        $fixedCount = 0;
        $createdFees = 0;

        foreach ($invoices as $invoice) {
            $student = $invoice->student;
            if (!$student) continue;

            $this->info("Processando Aluno: {$student->name} (ID: {$student->id})");

            $monthlyFeesData = MonthlyFee::where('student_id', $student->id)
                ->forMonth($year, $month)
                ->get();

            // 1. Resetar se estiver impedindo a fatura de ter valor
            foreach ($monthlyFeesData as $fee) {
                $shouldReset = false;
                
                // Caso A: Está 'paid' mas não tem pagamentos reais (Ghost payment)
                if ($fee->status === 'paid' && $fee->payments()->count() === 0) {
                    $this->warn(" - Detectada mensalidade ID {$fee->id} como 'paga' fantasma. Resetando...");
                    $shouldReset = true;
                }
                
                // Caso B: A fatura está zerada mas a mensalidade consta como paga com pagamentos Reias.
                // Se a fatura está 0,00 e o usuário quer consertar, precisamos resetar para recalcular.
                if ($invoice->total <= 0 && $fee->status === 'paid' && $force) {
                    $this->warn(" - Forçando reset da mensalidade PAGA ID {$fee->id} para corrigir fatura zerada.");
                    $shouldReset = true;
                }

                if ($shouldReset) {
                    $fee->update([
                        'status' => 'pending',
                        'amount_paid' => 0,
                    ]);
                }
            }

            // 2. Criar MonthlyFee se estiver faltando
            if ($student->monthly_fee > 0) {
                $enrollments = $student->activeEnrollments;
                foreach ($enrollments as $enrollment) {
                    $fee = MonthlyFee::firstOrCreate(
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
                    
                    if ($fee->wasRecentlyCreated) {
                        $createdFees++;
                        $this->info(" - Mensalidade criada: R$ {$fee->amount}");
                    } else if ($fee->amount <= 0) {
                        $fee->update(['amount' => $student->monthly_fee]);
                        $this->info(" - Valor da mensalidade atualizado: R$ {$fee->amount}");
                    }
                }
            }

            // 3. Limpar e Re-adicionar itens da fatura
            $invoice->items()->delete();
            
            // Recarregar fees após possíveis resets
            $monthlyFeesData = MonthlyFee::where('student_id', $student->id)
                ->forMonth($year, $month)
                ->get();

            foreach ($monthlyFeesData as $fee) {
                // Aqui está o pulo do gato: se for uma fatura de rascunho, 
                // queremos mostrar o valor da mensalidade nela, mesmo que o status da fee tenha sido 'pago' (se resetamos acima)
                if ($fee->amount > 0) {
                    $invoice->addItem(
                        'monthly_fee',
                        "Mensalidade {$invoice->reference}" . ($fee->classModel ? " - {$fee->classModel->name}" : ''),
                        1,
                        $fee->amount // Usamos o amount total para garantir que a fatura reflita o valor real
                    );
                }
            }

            // Material e Extras
            $materialFee = \App\Models\MaterialFee::where('student_id', $student->id)->forYear($year)->pending()->first();
            if ($materialFee && $materialFee->remaining_amount > 0) {
                $invoice->addItem('material_fee', "Taxa de Material {$year}", 1, $materialFee->remaining_amount);
            }

            $extraHours = \App\Models\AttendanceLog::where('student_id', $student->id)->forMonth($year, $month)->where('extra_charge', '>', 0)->get();
            if ($extraHours->sum('extra_charge') > 0) {
                $invoice->addItem('extra_hours', "Horas extras", 1, $extraHours->sum('extra_charge'));
            }

            $invoice->recalculateTotals();
            $this->info(" - Fatura recalculada. Novo total: R$ {$invoice->total}");
            $fixedCount++;
        }

        $this->info("\nReparo concluído!");
        $this->info("Invoices processadas: {$fixedCount}");
        $this->info("Novas mensalidades criadas: {$createdFees}");
        
        return 0;
    }

    private function makeDueDate($year, $month, $day)
    {
        return BillingCycle::makeDueDate((int) $year, (int) $month, (int) $day);
    }
}
