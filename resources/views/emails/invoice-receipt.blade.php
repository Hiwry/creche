<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1F2937; line-height: 1.5;">
    <p>Olá,</p>

    <p>
        Segue em anexo o recibo da fatura
        <strong>#{{ $invoice->invoice_number }}</strong>
        referente a <strong>{{ $invoice->reference }}</strong>.
    </p>

    <p>
        <strong>Aluno:</strong> {{ $invoice->student?->name ?? 'Aluno' }}<br>
        <strong>Valor:</strong> {{ $invoice->formatted_total }}<br>
        <strong>Vencimento:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
    </p>

    <p>Qualquer dúvida, estamos à disposição.</p>

    <p>Atenciosamente,<br>{{ $companyName }}</p>
</body>
</html>
