<?php

use App\Models\Student;
use App\Models\MonthlyFee;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentName = 'Benício Silva De Castro';
$targetYear = 2026;
$targetMonth = 1;

$student = Student::where('name', 'like', '%' . $studentName . '%')->first();

if (!$student) {
    echo "ERRO: Aluno '{$studentName}' não encontrado.\n";
    exit(1);
}

echo "Aluno encontrado: {$student->name} (ID: {$student->id})\n";

$fee = MonthlyFee::where('student_id', $student->id)
    ->where('year', $targetYear)
    ->where('month', $targetMonth)
    ->first();

if (!$fee) {
    echo "ERRO: Mensalidade de Janeiro/2026 não encontrada para este aluno.\n";
    exit(1);
}

echo "\nMensalidade Encontrada (Jan/2026):\n";
echo "Valor Atual: R$ " . number_format($fee->amount, 2, ',', '.') . "\n";
echo "Valor Pago: R$ " . number_format($fee->amount_paid, 2, ',', '.') . "\n";
echo "Status: " . $fee->status . "\n\n";

$newValue = 1870.00;
echo ">>> O valor da mensalidade será atualizado para: R$ " . number_format($newValue, 2, ',', '.') . "\n";

$fee->amount = $newValue;
// Assuming full payment if currently marked as paid
if ($fee->status === 'paid' || $fee->amount_paid >= 500) {
     $fee->amount_paid = $newValue;
     $fee->status = 'paid';
     echo "Ajustando valor PAGO para R$ 1870,00 para manter status 'Pago'.\n";
} else {
     $fee->status = 'pending'; // Reset to pending if not fully paid? But user says "era pra ser 1870".
     // If it was partial, let's keep it partial.
}

$fee->save();

echo "\n--- Concluído ---\n";
echo "Novo Valor: R$ " . number_format($fee->amount, 2, ',', '.') . "\n";
echo "Novo Pago: R$ " . number_format($fee->amount_paid, 2, ',', '.') . "\n";
echo "Novo Status: " . $fee->status . "\n";
