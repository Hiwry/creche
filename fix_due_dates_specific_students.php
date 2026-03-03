<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\MonthlyFee;
use App\Models\Student;
use App\Support\BillingCycle;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apply = in_array('--apply', $argv, true);

/**
 * Alvos solicitados:
 * - Joaquim, Pietro, Yasmin (na base está Yasmim) => dia 05
 * - Gabriela => dia 06
 * - José Benício => dia 10
 * - Maitê Seixas => dia 10
 */
$targets = [
    [
        'label' => 'Joaquim',
        'due_day' => 5,
        'exact_names' => [
            'Joaquim Valença Lima Nascimento',
            'Joaquim Valenca Lima Nascimento',
        ],
        'fallback_like' => '%Joaquim%',
    ],
    [
        'label' => 'Pietro',
        'due_day' => 5,
        'exact_names' => [
            'Pietro de Oliveira Ivanoff',
        ],
        'fallback_like' => '%Pietro%',
    ],
    [
        'label' => 'Yasmin',
        'due_day' => 5,
        'exact_names' => [
            'Yasmim Soares de Gusmão',
            'Yasmim Soares de Gusmao',
            'Yasmin Soares de Gusmão',
            'Yasmin Soares de Gusmao',
        ],
        'fallback_like' => '%Yasm%',
    ],
    [
        'label' => 'Gabriela',
        'due_day' => 6,
        'exact_names' => [
            'Gabriela Ferreira dos Santos',
        ],
        'fallback_like' => '%Gabriela%',
    ],
    [
        'label' => 'José Benício',
        'due_day' => 10,
        'exact_names' => [
            'José Benício Lucena Fontes Teles',
            'Jose Benicio Lucena Fontes Teles',
        ],
        'fallback_like' => '%Benicio%',
    ],
    [
        'label' => 'Maitê Seixas',
        'due_day' => 10,
        'exact_names' => [
            'Maitê Seixas Moraes',
            'Maite Seixas Moraes',
        ],
        'fallback_like' => '%Seixas%',
    ],
];

/**
 * Resolve aluno de forma segura:
 * 1) tenta por nome exato (lista de variantes)
 * 2) fallback por LIKE, mas exige resultado único
 */
function resolveStudent(array $target): Student
{
    foreach ($target['exact_names'] as $exact) {
        $student = Student::withTrashed()->where('name', $exact)->first();
        if ($student) {
            return $student;
        }
    }

    $matches = Student::withTrashed()
        ->where('name', 'like', $target['fallback_like'])
        ->get();

    if ($matches->count() === 1) {
        return $matches->first();
    }

    if ($matches->isEmpty()) {
        throw new RuntimeException("Aluno não encontrado para {$target['label']}.");
    }

    $names = $matches->pluck('name')->implode(', ');
    throw new RuntimeException(
        "Busca ambígua para {$target['label']} ({$target['fallback_like']}): {$names}"
    );
}

function updateOneStudent(Student $student, int $newDueDay, bool $apply): array
{
    $changes = [
        'student' => [
            'id' => $student->id,
            'name' => $student->name,
            'due_day_from' => $student->due_day,
            'due_day_to' => $newDueDay,
            'changed' => false,
        ],
        'monthly_fees_changed' => 0,
        'invoices_changed' => 0,
    ];

    if ((int) $student->due_day !== $newDueDay) {
        $changes['student']['changed'] = true;
        if ($apply) {
            $student->due_day = $newDueDay;
            $student->save();
        }
    }

    $fees = MonthlyFee::where('student_id', $student->id)
        ->where('status', '!=', 'cancelled')
        ->get();

    foreach ($fees as $fee) {
        $newDate = BillingCycle::makeDueDate((int) $fee->year, (int) $fee->month, $newDueDay)->toDateString();
        $currentDate = $fee->due_date ? $fee->due_date->toDateString() : null;

        if ($currentDate !== $newDate) {
            $changes['monthly_fees_changed']++;
            if ($apply) {
                $fee->due_date = $newDate;
                $fee->save();
            }
        }
    }

    $invoices = Invoice::where('student_id', $student->id)
        ->where('status', '!=', 'cancelled')
        ->get();

    foreach ($invoices as $invoice) {
        $newDate = BillingCycle::makeDueDate((int) $invoice->year, (int) $invoice->month, $newDueDay)->toDateString();
        $currentDate = $invoice->due_date ? $invoice->due_date->toDateString() : null;

        if ($currentDate !== $newDate) {
            $changes['invoices_changed']++;
            if ($apply) {
                $invoice->due_date = $newDate;
                $invoice->save();
            }
        }
    }

    return $changes;
}

echo $apply
    ? "MODO APPLY: alterações serão gravadas.\n\n"
    : "MODO DRY-RUN: nenhuma alteração será gravada. Use --apply para gravar.\n\n";

try {
    if ($apply) {
        DB::beginTransaction();
    }

    $totalStudentsChanged = 0;
    $totalFeesChanged = 0;
    $totalInvoicesChanged = 0;

    foreach ($targets as $target) {
        $student = resolveStudent($target);
        $result = updateOneStudent($student, (int) $target['due_day'], $apply);

        $totalStudentsChanged += $result['student']['changed'] ? 1 : 0;
        $totalFeesChanged += $result['monthly_fees_changed'];
        $totalInvoicesChanged += $result['invoices_changed'];

        $from = $result['student']['due_day_from'];
        $to = $result['student']['due_day_to'];
        $changedLabel = $result['student']['changed'] ? 'ALTERADO' : 'OK';

        echo "[{$changedLabel}] {$result['student']['name']} (ID {$result['student']['id']}): due_day {$from} -> {$to}\n";
        echo "  - monthly_fees due_date alteradas: {$result['monthly_fees_changed']}\n";
        echo "  - invoices due_date alteradas: {$result['invoices_changed']}\n\n";
    }

    if ($apply) {
        DB::commit();
    }

    echo "Resumo:\n";
    echo "- Alunos com due_day alterado: {$totalStudentsChanged}\n";
    echo "- Monthly fees alteradas: {$totalFeesChanged}\n";
    echo "- Invoices alteradas: {$totalInvoicesChanged}\n";
    echo $apply ? "\nConcluído com sucesso.\n" : "\nDry-run concluído.\n";
} catch (Throwable $e) {
    if ($apply) {
        DB::rollBack();
    }
    echo "ERRO: {$e->getMessage()}\n";
    exit(1);
}

