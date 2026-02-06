<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fatura {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111;">
    <p>Olá,</p>
    <p>
        Segue em anexo a fatura <strong>#{{ $invoice->invoice_number }}</strong>
        referente a <strong>{{ $invoice->reference }}</strong>.
    </p>
    <p>
        <strong>Aluno:</strong> {{ $invoice->student?->name ?? 'Aluno' }}<br>
        <strong>Vencimento:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
    </p>
    <p>Qualquer dúvida, estamos à disposição.</p>
    <p>Atenciosamente,<br>{{ $companyName }}</p>
</body>
</html>
