<?php

use App\Models\Student;
use App\Models\MonthlyFee;
use App\Models\Payment;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentName = 'Benício Silva De Castro';
echo "Procurando aluno: $studentName...\n";

$student = Student::where('name', 'like', '%' . $studentName . '%')->first();

if (!$student) {
    echo "ERRO: Aluno não encontrado.\n";
    exit(1);
}

// Find Fee for Jan 2026
$fee = MonthlyFee::where('student_id', $student->id)
    ->where('year', 2026)
    ->where('month', 1)
    ->first();

if (!$fee) {
    echo "ERRO: Mensalidade de Janeiro/2026 não encontrada.\n";
    exit(1);
}

// Find Payments
$payments = Payment::where('payable_type', MonthlyFee::class)
    ->where('payable_id', $fee->id)
    ->get();

$totalPaid = $payments->sum('amount');

echo "Mensalidade ID: {$fee->id}\n";
echo "Valor da Mensalidade: R$ " . number_format($fee->amount, 2, ',', '.') . "\n";
echo "Total em Pagamentos (Dashboard): R$ " . number_format($totalPaid, 2, ',', '.') . "\n";

if ($payments->count() == 0) {
    echo "ERRO: Nenhum pagamento encontrado para esta mensalidade.\n";
    // Should we create one?
    echo "Criando pagamento de R$ 1870,00...\n";
    Payment::create([
        'student_id' => $student->id,
        'payable_type' => MonthlyFee::class,
        'payable_id' => $fee->id,
        'amount' => 1870.00,
        'method' => 'pix', // Defaulting to PIX or maybe 'cash'? Assuming PIX for now or generic.
        'payment_date' => $fee->updated_at ?? now(), // Use update time or now
        'received_by' => 1, // Admin assumption
        'notes' => 'Correção automática de valor',
    ]);
    echo "Pagamento criado com sucesso!\n";
    
} elseif ($payments->count() == 1) {
    $payment = $payments->first();
    echo "Pagamento Único Encontrado: R$ " . number_format($payment->amount, 2, ',', '.') . "\n";
    
    if ($payment->amount != 1870.00) {
        $payment->amount = 1870.00;
        $payment->save();
        echo "Pagamento atualizado para R$ 1870,00.\n";
    } else {
        echo "Pagamento já está correto.\n";
    }
} else {
    echo "Múltiplos pagamentos encontrados:\n";
    foreach ($payments as $p) {
        echo "- R$ " . number_format($p->amount, 2, ',', '.') . "\n";
    }
    
    $diff = 1870.00 - $totalPaid;
    if ($diff > 0) {
        echo "Falta R$ " . number_format($diff, 2, ',', '.') . " para atingir R$ 1870,00.\n";
        echo "Criando pagamento complementar...\n";
        Payment::create([
            'student_id' => $student->id,
            'payable_type' => MonthlyFee::class,
            'payable_id' => $fee->id,
            'amount' => $diff,
            'method' => 'other',
            'payment_date' => now(),
            'received_by' => 1,
            'notes' => 'Ajuste de valor restante',
        ]);
        echo "Pagamento complementar criado.\n";
    } elseif ($diff < 0) {
        echo "Valor total de pagamentos (R$ $totalPaid) excede R$ 1870,00. Verifique manualmente.\n";
    } else {
        echo "Total de pagamentos está correto.\n";
    }
}

echo "\n--- Concluído ---\n";
